<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 4/30/17
 * Time: 3:49 PM
 */

namespace Models\Vertices\Project;


use DB\DB;
use Models\Core\VertexModel;
use Models\Edges\Assignment;
use Models\Vertices\Variable;
use Models\Vertices\Domain;
use Models\Vertices\Paper;
use Models\Vertices\User;
use Models\Edges\EnrolledIn;
use Models\Edges\PaperOf;
use Models\Edges\SubdomainOf;
use Models\Edges\VariableOf;

class Project extends VertexModel {

    static $collection = "projects";

    /**
     * @param $paper Paper
     */
    function addPaper( $paper , $priority = 0){
        PaperOf::create(
            $this->id(), $paper->id(), ['priority' => $priority]
        );
    }

    /**
     * @return int
     */
    public function updateVersion () {
        $version = $this->get('version') + 1;
        $this->update('version', $version);
        return $version;
    }

    /**
     * @param $domain Domain
     */
    function addDomain( $domain ){
        SubdomainOf::create(
            $this->id(), $domain->id(), []
        );
    }

    /**
     * @param $user User
     * @param $registrationCode
     */
    public function addUser ($user, $registrationCode) {
        if ($this->get('registrationCode') !== $registrationCode) {
            return 400;
        }

        $alreadyEnrolled = EnrolledIn::getByExample(['_from' => $user->id(), '_to' => $this->id()]);

        if ($alreadyEnrolled) {
            return 409;
        }

        $newEdge = EnrolledIn::create($this->id(), $user->id());
        if (!$newEdge) {
            return 500;
        }

        $queueItems = $this->getNextPaper($this->get('assignmentTarget'));
//        echo PHP_EOL.json_encode($queueItems);   // Damnit, Caleb. This was corrupting the JSON output.
        foreach ($queueItems as $queueItem) {
            if ($queueItem === false) {continue;}
            Assignment::assignByKey($queueItem['paperKey'], $user->key(), $this->get('version'));
        }
        return 200;
    }

    /**
     * Wipes this Study's structure
     * @param $searchDepth int the depth of the graph traversal
     */
    public function removeStructure ($searchDepth = 6) {
        DB::query(
            'FOR domain IN 1..@depth INBOUND @study_ID @@subdomain_of
                    FOR question, questionEdge IN INBOUND domain._id @@variable_of
                    REMOVE question IN @@variables OPTIONS { ignoreErrors: true }
                    REMOVE questionEdge IN @@variable_of OPTIONS { ignoreErrors: true }',
            [
                'depth' => $searchDepth,
                'study_ID' => $this->id(),
                '@subdomain_of' => SubdomainOf::$collection,
                '@variable_of' => VariableOf::$collection,
                '@variables' => Variable::$collection
            ]
        );

        DB::query(
            'FOR domain, subdomainEdge IN 1..@depth INBOUND @study_ID @@subdomain_of
                    REMOVE domain IN @@domains OPTIONS {ignoreErrors : true}
                    REMOVE subdomainEdge IN @@subdomain_of OPTIONS {ignoreErrors : true}',
            [
                'depth' => $searchDepth,
                'study_ID' => $this->id(),
                '@subdomain_of' => SubdomainOf::$collection,
                '@domains' => Domain::$collection
            ]
        );
    }

    /**
     * @return array
     */
    public function getStructureFlat(){
        $domains = [];
        foreach( $this->getTopLevelDomains() as $subdomain ){
            $domains[] = $this->recursiveGetDomain( $subdomain );
        }
        return $domains;
    }
    public function getVariablesFlat(){
        $AQL = "FOR domain IN 0..3 INBOUND @study_root @@domain_to_domain
                    FOR var IN INBOUND domain @@var_to_domain
                        RETURN var";
        $bindings = [
            'study_root'    =>  $this->id(),
            '@domain_to_domain'  =>  SubdomainOf::$collection,
            '@var_to_domain'     =>  VariableOf::$collection
        ];
        return DB::query($AQL, $bindings, true)->getAll();
    }

    public function getNextPaper($numPapers = 1){
        $queue = new PaperQueue($this->key());
        return $queue->next($numPapers);
    }

    public function getPapersFlat(){
        $AQL = "FOR paper IN INBOUND @study @@paper_to_study
                    RETURN paper";
        $bindings = [
            'study'     =>  $this->id(),
            '@paper_to_study'   =>  PaperOf::$collection
        ];
        return DB::query( $AQL, $bindings, true)->getAll();
    }

    private function getTopLevelDomains(){
        $AQL = "FOR domain in INBOUND @root @@domain_to_domain
                    RETURN domain";
        $bindings = [
            "root" => $this->id(),
            "@domain_to_domain" => SubdomainOf::$collection
        ];
        return DB::queryModel($AQL, $bindings, Domain::class);
    }
    private function recursiveGetDomain( $domain ){

        $variables  = $domain->getVariables();
        $subdomains = [];
        foreach ( $domain->getSubdomains() as $subdomain) {
            $subdomains[] = $this->recursiveGetDomain($subdomain);
        }

        $flat_vars = [];
        foreach ($variables as $var ){
            $flat_vars[] = $var->toArray();
        }

        $d = $domain->toArray();
        $v = [
            'variables'     =>  $flat_vars,
            'subdomains'    =>  $subdomains
        ];

        return array_merge( $d, $v );
    }
}