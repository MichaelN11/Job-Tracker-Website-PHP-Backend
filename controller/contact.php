<?php

// ****STILL NEED MANAGE CONTACTS PAGE****

/* ALL GET PAGE FUNCTIONS HERE */
function addContactPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Add Contact Page";
	$pageData['heading'] = "Job Tracker Add Contact Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/add_contact.html');
	$pageData['js'] = "Util^general^contact";
	$pageData['security'] = true;

	return $pageData;
}

function updateContactPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Update Contact Page";
	$pageData['heading'] = "Job Tracker Update Contact Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/update_contact.html');
	$pageData['js'] = "Util^general^contact";
	$pageData['security'] = true;

	return $pageData;
}

function manageContactPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Manage Contacts Page";
	$pageData['heading'] = "Job Tracker Manage Contacts Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/manage_contacts.html');
	$pageData['js'] = "Util^general^contact";
	$pageData['security'] = true;

	return $pageData;
}

function deleteContactPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Delete Contact Page";
	$pageData['heading'] = "Job Tracker Delete Contact Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/delete_contacts.html');
	$pageData['js'] = "Util^general^contact";
	$pageData['security'] = true;

	return $pageData;
}

/* ALL XHR FUNCTIONS HERE */

function addUpdateContact($dataObj) {
	require_once '../classes/Validation.php';
	require_once '../classes/General.php';
	require_once '../classes/Pdo_methods.php';
	
	$validate = new Validation();
	$error = false;
	$count = count($dataObj->elements);
	
	for ($i = 0; $i < $count; $i++) {
		if (!$validate->validate($dataObj->elements[$i]->regex, $dataObj->elements[$i]->value)) {
			$error = true;
			$dataObj->elements[$i]->status = 'error';
		}
	}
	
	if($error){
		$dataObj->masterstatus = 'fielderrors';
		$data = json_encode($dataObj);
		echo $data;
	}
	else {
		$general = new General();
		$pdo = new PdoMethods();
		
		// Creates bindings for adding or updating contact
		$elementNames = array('name^^str', 'workphone^^str', 'mobilephone^^str', 'email^^str');
		$bindings = $general->createBindedArray($elementNames, $dataObj);
		
		// Adding a new contact
		if ($dataObj->flag === 'addcontact') {
			// Check for duplicates before adding contact
			$duplicateResults = $general->checkDuplicates($dataObj, 'contact', $pdo);
			if ($duplicateResults !== 'error') {
				if (count($duplicateResults) !== 0) {
					$response = (object) [
				    'masterstatus' => 'error',
				    'msg' => 'There is already a contact with that email',
					];
				echo json_encode($response);
				}
				else {
					$sql = "INSERT INTO contact (name, work_phone, mobile_phone, email) VALUES (:name, :workphone, :mobilephone, :email)";
					
					$result = $pdo->otherBinded($sql, $bindings);
					if($result = 'noerror'){
						$response = (object) [
							'masterstatus' => 'success',
							'msg' => 'The contact has been added',
						];
						echo json_encode($response);
					}
					else {
						$response = (object) [
							'masterstatus' => 'error',
							'msg' => 'There was an error with our sql statement',
						];
						echo json_encode($response);
					}
				}
			}
			else {
				$object = (object) [
					'masterstatus' => 'error',
					'msg' => 'There was a problem adding the contact',
				];
				echo json_encode($object);
			}
		}
		// Updating an existing contact
		else {
			array_push($bindings, array(':contactId', $dataObj->contactId, 'str'));
			$sql = "UPDATE contact SET name=:name, work_phone=:workphone, mobile_phone=:mobilephone, email=:email WHERE id=:contactId";
			$result = $pdo->otherBinded($sql, $bindings);
			
			if($result = 'noerror'){
				$object = (object) [
					'masterstatus' => 'success',
					'msg' => 'Contact has been updated'
				];
				echo json_encode($object);
			}
			else {
				$object = (object) [
					'masterstatus' => 'error',
					'msg' => 'There was an error with our sql statement',
				];
				echo json_encode($object);
			}
		}			
	}
}

function getContactList($dataObj) {
	require_once "../classes/Pdo_methods.php";
	$pdo = new PdoMethods();
	$sql = "SELECT id, name, email FROM contact";
	$records = $pdo->selectNotBinded($sql);
	if($records == 'error'){
    	echo 'There was an error getting the contacts list';
    }
    else {
		// Create the dropdown list
    	if(count($records) != 0){
        	$contacts = '<select id="contlst" class="form-control">
            <option value="0">Select a contact</option>';
        	foreach ($records as $row) {
        		$contacts .= "<option value=".$row['id'].">".$row['name']." - ".$row['email']."</option>";
        	}
        	$contacts .= '</select>';

            $response = (object) [
                'masterstatus' => 'success',
                'list' => $contacts
             ];
            $data = json_encode($response);
            echo $data;
	    }
	    else {
	    	$response = (object) [
                'masterstatus' => 'error',
                'msg' => 'No contacts found',
             ];
            $data = json_encode($response);
            echo $data;
	    }
    }
}
	
// Creates the update contact form
function getContact($dataObj){
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT * FROM contact WHERE id=:id";
	$bindings = array(
		array(':id',$dataObj->id,'int'),
	);

	$records = $pdo->selectBinded($sql, $bindings);

	if($records == 'error'){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with the sql statement',
		];
		echo json_encode($object);
	}
	else{
		if(count($records) != 0){
			
			$table = '<div class="row">    
			      <div class="col-md-6">
				  <div class="form-group">
					<label for="name">Name:</label>
					<input type="text" class="form-control" id="name" name="name" value="'.$records[0]['name'].'">
				  </div>
				</div>
			  </div>
			  <div class="row">
				<div class="col-md-6">
				  <div class="form-group">
					<label for="workphone">Work Phone:</label>
					<input type="text" class="form-control" name="workphone" id="workphone" value="'.$records[0]['work_phone'].'">
				  </div>
				</div>
			  </div>
			  <div class="row">
				<div class="col-md-6">
				  <div class="form-group">
					<label for="mobilephone">Mobile Phone: (optional)</label>
					<input type="text" class="form-control" name="mobilephone" id="mobilephone" value="'.$records[0]['mobile_phone'].'">
				  </div>
				</div>
			  </div>
			  <div class="row">
				<div class="col-md-6">
					<div class="form-group">
					  <label for="email">Email:</label>
					  <input type="text" class="form-control" name="email" id="email" value="'.$records[0]['email'].'">
					</div>
				</div>
			  </div>
				
			  <div class="row">
				<div class="col-md-6">
				  <div class="form-group">
					<input type="button" name="updatecontact" id="updatecontactBtn" class="btn btn-primary" value="Update Contact" />
				  </div>
				</div>
			  </div>
			  </div>';

			echo $table; 
		}
		else {
			echo "Error: No contacts found.";
		}
	}
}

function deleteContact($dataObj) {
	require_once '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	
	$sql = "DELETE FROM contact WHERE id = :id";
	$bindings = array(
		array(':id', $dataObj->contId, 'int')
	);
	
	$result = $pdo->otherBinded($sql, $bindings);
	
	if($result = 'noerror'){
		$object = (object) [
			'masterstatus' => 'success',
			'msg' => 'Record Deleted'
		];
		echo json_encode($object);
	}
	else {
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Could not delete record'
			];
		echo json_encode($object);
	}
}

// Create the delete contact table
function contactTable(){
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, name FROM contact";
	$records = $pdo->selectNotBinded($sql);
	if($records == 'error'){
    	echo 'There has been an error processing your request';
    }
    else {
    	if(count($records) != 0){
        	$contacts = '<table class="table table-bordered table-striped" id="contTable">';
        	$contacts .= '<thead><tr><th>Contact Name</th><th>Delete</th></tr></thead><tbody>';

        	foreach ($records as $row) {
        		$contacts .= "<tr><td style='width: 90%'>".$row['name']."</td>";
        		$contacts .= "<td style='width: 10%'><input type='button' class='btn btn-danger' value='Delete' id='".$row['id']."'></td></tr>";
        	}
        	$contacts .= '</tbody></table>';
        	echo $contacts;
	    }
	    else {
	    	echo 'No contacts found';
	    }
    }
}

// Manage contact interface
// Echoes an object with 'name', 'associations', and 'accounts'
// from job_contact table
function mcInterface($dataObj) {
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	
	$bindings = array(
		array(':id', $dataObj->contId, 'int')
	);
	$sql = "SELECT name FROM contact WHERE id = :id";
	$nameRecords = $pdo->selectBinded($sql, $bindings);
	
	$sql = "SELECT id, name FROM account";
	$accountRecords = $pdo->selectNotBinded($sql);
	
	// function that returns the html for the associations table as a string, or 'error'
	$associations = getAssocTable($dataObj->contId);
	
	if($nameRecords == 'error' || $associations == 'error' || $accountRecords == 'error'){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with the sql statement',
		];
		echo json_encode($object);
	}
	else {	
		$accounts = '';
		if(count($accountRecords) != 0){
        	$accounts = '<select id="acclst" class="form-control">
            <option value="0">Select an account</option>';
        	foreach ($accountRecords as $row) {
        		$accounts .= "<option value=".$row['id'].">".$row['name']."</option>";
        	}
        	$accounts .= '</select>';
	    }
	    else {
	    	$accounts = 'No accounts found';
	    }
		
		$object = (object) [
			'masterstatus' => 'success',
			'name' => $nameRecords[0]['name'],
			'associations' => $associations,
			'accounts' => $accounts,
		];
		echo json_encode($object);
	}
	
}

// From acctId, jobId, contId, add the association to database 
// and echo an object with the associations table
function addAssoc($dataObj) {
	require '../classes/Pdo_methods.php';
	
	$pdo = new PdoMethods();	
	
	$sql = "INSERT INTO job_contact (account_id, job_id, contact_id) VALUES (:accountId, :jobId, :contactId)";
	$bindings = array(
		array(':accountId', $dataObj->acctId, 'int'),
		array(':jobId', $dataObj->jobId, 'int'),
		array(':contactId', $dataObj->contId, 'int'),
	);
	
	$result = $pdo->otherBinded($sql, $bindings);
	
	if($result == 'error'){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Error: Not able to add the association'
		];
		echo json_encode($object);
	}
	else {
		// Function returns html for the contact associations table
		$associations = getAssocTable($dataObj->contId);
			
		if ($associations == 'error') {
				$object = (object) [
				'masterstatus' => 'error',
				'msg' => 'There was an error with the sql statement',
			];
			echo json_encode($object);
		}
		else {
			$object = (object) [
				'masterstatus' => 'success',
				'associations' => $associations,
				];
			echo json_encode($object);
		}
	}
}

// From acctId, jobId, contId, delete the association from the database 
// and echo an object with the associations table
function delAssoc($dataObj) {
	require '../classes/Pdo_methods.php';
	
	$pdo = new PdoMethods();	
	
	$sql = "DELETE FROM job_contact WHERE account_id = :accountId AND job_id = :jobId AND contact_id = :contactId";
	$bindings = array(
		array(':accountId', $dataObj->acctId, 'int'),
		array(':jobId', $dataObj->jobId, 'int'),
		array(':contactId', $dataObj->contId, 'int'),
	);
	
	$result = $pdo->otherBinded($sql, $bindings);
	
	if($result == 'error'){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Error: Not able to delete the association'
		];
		echo json_encode($object);
	}
	else {
		// Function returns html for the contact associations table
		$associations = getAssocTable($dataObj->contId);
			
		if ($associations == 'error') {
				$object = (object) [
				'masterstatus' => 'error',
				'msg' => 'There was an error with the sql statement',
			];
			echo json_encode($object);
		}
		else {
			$object = (object) [
				'masterstatus' => 'success',
				'associations' => $associations,
				];
			echo json_encode($object);
		}
	}
}

// Returns the html for the table that displays contact associations
function getAssocTable($contId) {
		require_once '../classes/Pdo_methods.php';
		$pdo = new PdoMethods();
		
		$table = '';
		
		// Query to get the contact's job associations from many-to-many relationship
		$sql = "SELECT account.name AS accountname, job.name AS jobname, job.id AS jobid, account.id AS accountid FROM account, job, job_contact WHERE account.id = job_contact.account_id AND job.id = job_contact.job_id AND job_contact.contact_id = :id";
		$bindings = array(
			array(':id', $contId, 'int')
		);
		$assocRecords = $pdo->selectBinded($sql, $bindings);
		
		if ($assocRecords == 'error') {
			$table = 'error';
		}		
		else {
			$associations = "";
			if(count($assocRecords) != 0){
				$associations = '<table class="table table-bordered table-striped" id="contTable">';
				$associations .= '<thead><tr><th>Account</th><th>Job</th><th>Delete</th></tr></thead><tbody>';

				foreach ($assocRecords as $row) {
					$associations .= "<tr><td style='width: 45%'>".$row['accountname']."</td>";
					$associations .= "<td style='width: 45%'>".$row['jobname']."</td>";
					$associations .= "<td style='width: 10%'><input type='button' class='btn btn-danger' value='Delete' id='".$row['accountid']."&&&".$row['jobid']."'></td></tr>";
				}
				$associations .= '</tbody></table>';
			}
			else {
				$associations = 'There are no associations for this contact';
			}
			
			$table = $associations;
		}
	return $table;
}


?>