<?php

class Application_Service_DataManagers {

    public function addUser($params){
		$userDb = new Application_Model_DbTable_DataManagers();
        return $userDb->addUser($params);
    }
    
    public function updateUser($params){
        $userDb = new Application_Model_DbTable_DataManagers();
        return $userDb->updateUser($params);
    }
    public function getAllUsers($params){
        $userDb = new Application_Model_DbTable_DataManagers();
        return $userDb->getAllUsers($params);
    }
    
    public function getUserInfo($userId = null){

		$userDb = new Application_Model_DbTable_DataManagers();
        if($userId == null){
            $authNameSpace = new Zend_Session_Namespace('Zend_Auth');
            $userId = $authNameSpace->UserID;
        }
		return $userDb->getUserDetails($userId);
    }    
    public function getUserInfoBySystemId($userSystemId = null){

		$userDb = new Application_Model_DbTable_DataManagers();
        if($userSystemId == null){
            $authNameSpace = new Zend_Session_Namespace('Zend_Auth');
            $userSystemId = $authNameSpace->UserSystemID;
        }
		return $userDb->getUserDetailsBySystemId($userSystemId);
    }
	
	public function resetPassword($email){
		$userDb = new Application_Model_DbTable_DataManagers();
		$newPassword = $userDb->resetPasswordForEmail($email);
		$sessionAlert = new Zend_Session_Namespace('alertSpace');
		if($newPassword != false){
			$common = new Application_Service_Common();
			$message = "Hi,<br/> We have reset your password. Please use <strong>$newPassword</strong> as your new password.<br/><small>This is a system generated email. Please do not reply.</small>";
			$fromMail = Application_Service_Common::getConfig('admin-email');			
			$fromName = Application_Service_Common::getConfig('admin-name');			
			$common->sendMail($email,null,null,"Password Reset - e-PT",$message,$fromMail,$fromName);
			$sessionAlert->message = "Your password has been reset. Please check your registered mail id for the instructions.";
			$sessionAlert->status = "success";
		}else{
			$sessionAlert->message = "Sorry, we could not reset your password. Please make sure that you enter your registered primary email id";
			$sessionAlert->status = "failure";
		}
	}
	
	
	public function getDataManagerList(){
		$userDb = new Application_Model_DbTable_DataManagers();
		return $userDb->getAllDataManagers();
	}
	
	public function changePassword($oldPassword,$newPassword){
		$userDb = new Application_Model_DbTable_DataManagers();
		$newPassword = $userDb->updatePassword($oldPassword,$newPassword);
		$sessionAlert = new Zend_Session_Namespace('alertSpace');
		if($newPassword != false){
			$sessionAlert->message = "Your password has been updated.";
			$sessionAlert->status = "success";
		}else{
			$sessionAlert->message = "Sorry, we could not update your password. Please try again";
			$sessionAlert->status = "failure";
		}
	}
	
}
