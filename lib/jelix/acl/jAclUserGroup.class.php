<?php
/**
* @package     jelix
* @subpackage  acl
* @author      Laurent Jouanneau
* @copyright   2006 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
* @since 1.0a3
*/

/**
 * Use this class to register or unregister users in the acl system, and to manage user groups
 * It needs the acl module.
 * @package     jelix
 * @subpackage  acl
 * @static
 */
class jAclUserGroup {

    /**
     * @internal The constructor is private, because all methods are static
     */
    private function __construct (){ }

    /**
     * get the list of the users of a group
     * @param int $groupid  id of the user group
     * @return array a list of users object (dao records)
     */
    public static function getUsersList($groupid){
      $dao = jDao::get('jxacl~jaclusergroup');
      return $dao->getUsersGroup($groupid);
    }

    /**
     * register a user in the acl system
     *
     * For example, this method is called by the acl module when responding
     * to the event generated by the auth module when a user is created.
     * When a user is registered, a private group is created.
     * @param string $login the user login
     * @param boolean $defaultGroup if true, the user become the member of default groups
     */
    public static function createUser($login, $defaultGroup=true){
      $daousergroup = jDao::get('jxacl~jaclusergroup');
      $daogroup = jDao::get('jxacl~jaclgroup');
      $usergrp = jDao::createRecord('jxacl~jaclusergroup');
      $usergrp->login =$login;

      // si $defaultGroup -> assign le user aux groupes par defaut
      if($defaultGroup){
         $defgrp = $daogroup->getDefaultGroups();
         foreach($defgrp as $group){
            $usergrp->id_aclgrp = $group->id_aclgrp;
            $daousergroup->insert($usergrp);
         }
      }

      // creation d'un groupe personnel
      $persgrp = jDao::createRecord('jxacl~jaclgroup');
      $persgrp->name = $login;
      $persgrp->grouptype = 2;
      $persgrp->ownerlogin = $login;

      $daogroup->insert($persgrp);
      $usergrp->id_aclgrp = $persgrp->id_aclgrp;
      $daousergroup->insert($usergrp);
    }

    /**
     * add a user into a group
     *
     * (a user can be a member of several groups)
     * @param string $login the user login
     * @param int $groupid the group id
     */
    public static function addUserToGroup($login, $groupid){
      $daousergroup = jDao::get('jxacl~jaclusergroup');
      $usergrp = jDao::createRecord('jxacl~jaclusergroup');
      $usergrp->login =$login;
      $usergrp->id_aclgrp = $groupid;
      $daousergroup->insert($usergrp);
    }

    /**
     * remove a user from a group
     * @param string $login the user login
     * @param int $groupid the group id
     */
    public static function removeUserFromGroup($login,$groupid){
      $daousergroup = jDao::get('jxacl~jaclusergroup');
      $daousergroup->delete($login,$groupid);
    }

    /**
     * unregister a user in the acl system
     * @param string $login the user login
     */
    public static function removeUser($login){
      $daogroup = jDao::get('jxacl~jaclgroup');
      $daoright = jDao::get('jxacl~jaclrights');
      $daousergroup = jDao::get('jxacl~jaclusergroup');

      // recupere le groupe priv�
      $privategrp = $daogroup->getPrivateGroup($login);
      if(!$privategrp) return;

      // supprime les droits sur le groupe priv� (jacl_rights)
      $daoright->deleteByGroup($privategrp->id_aclgrp);

      // supprime le groupe personnel du user (jacl_group)
      $daogroup->delete($privategrp->id_aclgrp);

      // l'enleve de tous les groupes (jacl_users_group)
      $daousergroup->deleteByUser($login);
    }

    /**
     * create a new group
     * @param string $name its name
     * @return int the id of the new group
     */
    public static function createGroup($name){
        $group = jDao::createRecord('jxacl~jaclgroup');
        $group->name=$name;
        $group->grouptype=0;
        $daogroup = jDao::get('jxacl~jaclgroup');
        $daogroup->insert($group);
        return $group->id_aclgrp;
    }

    /**
     * set a group to be default (or not)
     *
     * there can have several default group. A default group is a group
     * where a user is assigned to during its registration
     * @param int $groupid the group id
     * @param boolean $default true if the group is to be default, else false
     */
    public static function setDefaultGroup($groupid, $default=true){
       $daogroup = jDao::get('jxacl~jaclgroup');
       if($default)
         $daogroup->setToDefault($groupid);
       else
         $daogroup->setToNormal($groupid);
    }

    /**
     * change the name of a group
     * @param int $groupid the group id
     * @param string $name the new name
     */
    public static function updateGroup($groupid, $name){
       $daogroup = jDao::get('jxacl~jaclgroup');
       $daogroup->changeName($groupid,$name);
    }

    /**
     * delete a group from the acl system
     * @param int $groupid the group id
     */
    public static function removeGroup($groupid){
       $daogroup = jDao::get('jxacl~jaclgroup');
       $daoright = jDao::get('jxacl~jaclrights');
       $daousergroup = jDao::get('jxacl~jaclusergroup');
       // enlever tout les droits attach� au groupe
       $daoright->deleteByGroup($groupid);
       // enlever les utilisateurs du groupe
       $daousergroup->deleteByGroup($groupid);
       // suppression du groupe
       $daogroup->delete($groupid);
    }

    /**
     * return a list of group.
     *
     * if a login is given, it returns only the groups of the user.
     * Else it returns all groups (except private groups)
     * @param string $login an optional login
     * @return array a list of groups object (dao records)
     */
    public static function getGroupList($login=''){
        if ($login === '') {
            $daogroup = jDao::get('jxacl~jaclgroup');
            return $daogroup->findAllPublicGroup();
        }else{
            $daogroup = jDao::get('jxacl~jaclgroupsofuser');
            return $daogroup->getGroupsUser($login);
        }
    }
}

?>