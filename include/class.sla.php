<?php
/*********************************************************************
    class.sla.php

    SLA

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class SLA {

    var $id;

    var $info;

    function SLA($id){
        $this->id=0;
        $this->load($id);
    }

    function load($id) {

        $sql='SELECT * FROM '.SLA_TABLE.' WHERE id='.db_input($id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['id'];
            $this->info=$info;
            return true;
        }
        return false;
    }

    function reload() {
        return $this->load($this->getId());
    }

    function getId(){
        return $this->id;
    }

    function getName(){
        return $this->info['name'];
    }

    function getGracePeriod(){
        return $this->info['grace_period'];
    }
        
    function getNotes(){
        return $this->info['notes'];
    }

    function getInfo(){
        return  $this->info;
    }

    function isActive(){
        return ($this->info['isactive']);
    }

    function sendAlerts(){
        return (!$this->info['disable_overdue_alerts']);
    }

    function priorityEscalation(){
        return ($this->info['enable_priority_escalation']);
    }

    function update($vars,&$errors){
        if(SLA::save($this->getId(),$vars,$errors)){
            $this->reload();
            return true;
        }
        
        return false;
    }

    function delete(){
        global $cfg;

        if($cfg && $cfg->getDefaultSLAId()==$this->getId())
            return false;

        $id=$this->getId();
        $sql='DELETE FROM '.SLA_TABLE.' WHERE id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())){
            db_query('UPDATE '.DEPT_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TOPIC_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TICKET_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
        }

        return $num;
    }

    /** static functions **/
    function create($vars,&$errors){
        return SLA::save(0,$vars,$errors);
    }

    function getIdByName($name){

        $sql='SELECT id FROM '.SLA_TABLE.' WHERE name='.db_input($name);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    function lookup($id){
        return ($id && is_numeric($id) && ($sla= new SLA($id)) && $sla->getId()==$id)?$sla:null;
    }

    function save($id,$vars,&$errors){


        if(!$vars['grace_period'])
            $errors['grace_period']='Grace period required';
        elseif(!is_numeric($vars['grace_period']))
            $errors['grace_period']='Numeric value required (in hours)';
            
        if(!$vars['name'])
            $errors['name']='Name required';
        elseif(($sid=SLA::getIdByName($vars['name'])) && $sid!=$id)
            $errors['name']='Name already exists';

        if($errors) return false;

        $sql=' updated=NOW() '.
             ',isactive='.db_input($vars['isactive']).
             ',name='.db_input($vars['name']).
             ',grace_period='.db_input($vars['grace_period']).
             ',disable_overdue_alerts='.db_input(isset($vars['disable_overdue_alerts'])?1:0).
             ',enable_priority_escalation='.db_input(isset($vars['enable_priority_escalation'])?1:0).
             ',notes='.db_input($vars['notes']);

        if($id) {
            $sql='UPDATE '.SLA_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']='Unable to update SLA. Internal error occurred';
        }else{
            $sql='INSERT INTO '.SLA_TABLE.' SET '.$sql.',created=NOW() ';
            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']='Unable to add SLA. Internal error';
        }

        return false;
    }
}
?>