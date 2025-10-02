<?php

//This COmanage Registry enrollment plugin is intended to be used with
//an  enrollment flow for ITRSS. When the EF is finalized, a uid will be 
//created for the CO Person which takes the form of <domain name without 
//top level domain>-<username>. Example: shayna@example.edu becomes
//example-shayna. 

App::uses('CoPetitionsController', 'Controller');

class ItrssUidEnrollerCoPetitionsController extends CoPetitionsController {

  public $name = "ItrssUidEnrollerCoPetitions";
  public $uses = array("CoPetition", "HistoryRecord");

   /**
   * Plugin functionality following finalize step
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
  protected function execute_plugin_finalize($id, $onFinish) {

    //get the petition, any uid Identifiers already on the EnrolleeCoPerson, and any mail COPetitionAttribute
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain']['EnrolleeCoPerson']['Identifier'] = array('conditions' => array('Identifier.type' => 'uid'));
    $args['contain']['CoPetitionAttribute'] = array('conditions' => array('CoPetitionAttribute.attribute' => 'mail')); 

    $petition = $this->CoPetition->find('first', $args);

    if (!isset($petition)) {
      throw new InvalidArgumentException(_txt('er.notfound',
                                         array(_txt('ct.petitions.1'),
                                               $id)));
    }
       
    $coId = $petition['CoPetition']['co_id'];
    $coPersonId = $petition['CoPetition']['enrollee_co_person_id'];

    $enteredEmail = !empty($petition['CoPetitionAttribute']) ? $petition['CoPetitionAttribute'][0]['value'] : null;

    // use the entered email to generate a new uid
    if (isset($enteredEmail)) {
      $emailComponents = explode("@", $enteredEmail);
      if (sizeof($emailComponents) > 2) {
        throw new InvalidArgumentException(_txt('er.iue.bad_email_address'), array($enteredEmail));
      }  else {
        $username = $emailComponents[0];
        $domain = $emailComponents[1];

        //Strip top level domain off of the domain portion of the email address
        $domain = strrchr($domain, '.', true);

        //Remove non-alphanumeric characters from the username and domain
        $pattern = '/[^a-zA-Z0-9]/';
        $domain = preg_replace($pattern, "", $domain);
        $username = preg_replace($pattern, "", $username);

        //construct the base identifier
        $uid = $domain . "-" . $username;

        //check if this identifier has already been assigned. If so, keep adding collision numbers on until we get a good one. 
        $goodUid = false;

        try {
          $this->CoPetition->EnrolleeCoPerson->Identifier->checkAvailability($uid, 'uid', $coId);
          $goodUid = true;
        }
        catch(Exception $e) {
          $collision = 2;
          while ($collision < 101) {
            $newUid = $uid . "$collision";
            try {
              $this->CoPetition->EnrolleeCoPerson->Identifier->checkAvailability($newUid, 'uid', $coId);
              $goodUid = true;
              $uid = $newUid;
              break;
            }
            catch(Exception $e2) {
             $collision += 1;
            }
          } 
        }

        if (!$goodUid) {
          throw new OverflowException(_txt('er.iue.too_many_collisions'));
        } else {

          $actorCoPersonId = $this->Session->read('Auth.User.co_person_id');
          $actorApiUserId = null;

          // if a uid was found, delete it first
          if (!empty($petition['EnrolleeCoPerson']['Identifier'])) {
            $this->CoPetition->EnrolleeCoPerson->Identifier->delete($petition['EnrolleeCoPerson']
                                                                   ['Identifier'][0]['id']);
            // The delete method does not return a value to help determine if it worked. So check whether it is still there
            $checkDelete = $this->CoPetition->EnrolleeCoPerson->Identifier->find('first', array('conditions' => 
                                                                                    array('Identifier.id' => $petition['EnrolleeCoPerson']['Identifier'][0]['id'],
                                                                                          'Identifier.deleted' => false)));
            if (!empty($checkDelete)) {
              throw new RuntimeException(_txt('er.iue.no_delete')); 
            }

            //create a history record for the deletion
            $changeStr = _txt('rs.deleted-a3', array(_txt('ct.identifiers.1')));
            $changeStr .= ": " . $this->CoPetition->EnrolleeCoPerson->Identifier->changesToString(null, $petition['EnrolleeCoPerson'], $coId);
            $this->HistoryRecord->record($coPersonId, null, null,  $actorCoPersonId, ActionEnum::CoPersonEditedPetition, $changeStr, null, null, null,
                                                     $actorApiUserId);
          } 

          //save the custom uid
          $identifierData = array();
          $identifierData['Identifier']['identifier'] = $uid;
          $identifierData['Identifier']['type'] = 'uid';
          $identifierData['Identifier']['login'] = false;
          $identifierData['Identifier']['co_person_id'] = $petition['EnrolleeCoPerson']['id'];
          $identifierData['Identifier']['status'] = StatusEnum::Active;

          // We need to update the Identifier validation rule
          $this->CoPetition->EnrolleeCoPerson->Identifier->validate['type']['content']['rule'][1]['coid'] = $coId; 

          //reset model state
          $this->CoPetition->EnrolleeCoPerson->Identifier->create($identifierData); 
          
          //save the new identifier and write a history record
          if($this->CoPetition->EnrolleeCoPerson->Identifier->save($identifierData,
                                               array('provision' => false))) {
            $changeStr = _txt('rs.added-a3', array(_txt('ct.identifiers.1')));
            $changeStr .= ": " . $this->CoPetition->EnrolleeCoPerson->Identifier->changesToString($identifierData, null, $coId);
            $this->HistoryRecord->record($coPersonId, null, null,  $actorCoPersonId, ActionEnum::CoPersonEditedPetition, $changeStr, null, null, null,
                                                     $actorApiUserId);
          } else {
            throw new RuntimeException(_txt('er.iue.cant_save')); 
          }
        }                                                            
      }
      
    } else {
      throw new InvalidArgumentException(_txt('er.iue.no_email_attribute'));
    }
    $this->redirect($onFinish);
  }
}
