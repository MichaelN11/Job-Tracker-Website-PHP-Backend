<?php
/* ALL GET PAGE FUNCTIONS HERE */
function addJobPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Add Job Page";
	$pageData['heading'] = "Job Tracker Add Job Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/add_job.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function viewJobContactsPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "View Job Contacts Page";
	$pageData['heading'] = "Job Tracker View Job Contacts Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/view_job_contacts.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function addJobNotePage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Add Job Notes Page";
	$pageData['heading'] = "Job Tracker Add Job Notes Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/add_job_notes.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function viewUpdateDeleteNotePage(){
	$pageData['base'] = "../";
	$pageData['title'] = "View/Update/Delete Job Notes Page";
	$pageData['heading'] = "Job Tracker View/Update/Delete Job Notes Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/view_delete_job_notes.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function addJobAssetPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Add Job Assets Page";
	$pageData['heading'] = "Job Tracker Add Job Assets Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/add_job_assets.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function viewDeleteJobAssetPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "View or Delete Job Assets Page";
	$pageData['heading'] = "Job Tracker View or Delete Job Assets Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/view_delete_job_assets.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function addJobHoursPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Add Job Hours Page";
	$pageData['heading'] = "Job Tracker Add Job Hours Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/add_job_hours.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function updateDeleteJobHoursPage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Update or Delete Job Hours Page";
	$pageData['heading'] = "Job Tracker Update or Delete Job Hours Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/update_delete_hours.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}
function printInvoicePage(){
	$pageData['base'] = "../";
	$pageData['title'] = "Print Invoice Page";
	$pageData['heading'] = "Job Tracker Print Invoice Page";
	$pageData['nav'] = true;
	$pageData['content'] = file_get_contents('views/admin/print_invoice.html');
	$pageData['js'] = "Util^general^job";
	$pageData['security'] = true;

	return $pageData;
}

function addJob($dataObj) {
	require_once '../classes/Pdo_methods.php';
	require_once '../classes/Validation.php';
	require_once '../classes/General.php';
		
	$validate = new Validation();
	$error = false;
	$count = count($dataObj->elements);

	// Check for field errors
	for ($i = 0; $i < $count; $i++){
		if(!$validate->validate($dataObj->elements[$i]->regex, $dataObj->elements[$i]->value)){
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
		$General = new General();
		$pdo = new PdoMethods();

		// Check for duplicate job names
		$result = $General->checkDuplicates($dataObj, 'job', $pdo);
		
		// Get the account folder from the database
		$accountFolder = '';
		$accountError = false;
		$sql = "SELECT folder FROM account WHERE id = :id";
		$bindings = array(
			array(':id', $dataObj->accountId, "int")
			);
		$records = $pdo->selectBinded($sql, $bindings);
		if($records == 'error'){
			$accountError = true;
			$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with the sql statement',
			];
			echo json_encode($object);
		}
		else if (count($records) != 1) {
			$accountError = true;
			$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error finding the account',
			];
			echo json_encode($object);
		}
		else {
			$accountFolder = $records[0]['folder']."/";
		}
		
		if($result != 'error' && $accountError === false){
			if(count($result) != 0){
				$response = (object) [
				    'masterstatus' => 'error',
				    'msg' => 'There is already a job by that name',
				  ];
				echo json_encode($response);
			}
			else {
				// Get the job name
				$name = '';
				$count = count($dataObj->elements);
				for ($i = 0; $i < $count; $i++) {
					if($dataObj->elements[$i]->id === 'name'){
						$name = $dataObj->elements[$i]->value;
						break;
					}
				}

				// Create the folder and add a timestamp
				$foldername = $name.time();
				$foldername = str_replace(" ", "_", $foldername);
				$foldername = strtolower ($foldername);

				// Give folder 777 permissions
				$path = $accountFolder.$foldername;
				$dir = mkdir($path, 0777);

				$sql = "INSERT INTO job (account_id, name, folder) VALUES (:account_id, :name, :folder)";
				$bindings = array(
					array(':account_id', $dataObj->accountId, "int"),
					array(':name', $name, "str"),
					array(':folder', $path, "str"),
				);
			
				// If directory was created, success, otherwise send error
				if($dir){
					$result = $pdo->otherBinded($sql, $bindings);
										
					if($result = 'noerror'){
						$response = (object) [
					    	'masterstatus' => 'success',
					    	'msg' => 'The job has been added',
						];
						echo json_encode($response);

					}
					else {
						$response = (object) [
					    	'masterstatus' => 'error',
					    	'msg' => 'There was a problem adding the job',
						];
						echo json_encode($response);
					}
							
				}
				else {
					$response = (object) [
					    	'masterstatus' => 'error',
					    	'msg' => 'There was a problem making the job directory',
						];
						echo json_encode($response);
				}

			}
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

// Using jobId, echoes object with 'table' that contains html for displaying contacts associated with the job
function getjobcontacts($dataObj) {
	require_once '../classes/Pdo_methods.php';
	
	$pdo = new PdoMethods();
	$sql = 'SELECT contact.name AS name, contact.work_phone AS work_phone, contact.mobile_phone AS mobile_phone, contact.email AS email FROM contact, job_contact WHERE contact.id = job_contact.contact_id AND job_contact.job_id = :jobId';
	$bindings = array(
		array(':jobId', $dataObj->jobId, "int")
		);
	$records = $pdo->selectBinded($sql, $bindings);
	
	if($records == 'error'){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with the SQL statement.'
		];
		echo json_encode($object);
	}
	else {
		$table = "";
		if(count($records) != 0){
			$table = '<table class="table table-bordered table-striped" id="contTable">';
			$table .= '<thead><tr><th>Name</th><th>Email</th><th>Work Phone</th><th>Mobile Phone</th></tr></thead><tbody>';

			foreach ($records as $row) {
				$table .= "<tr><td style='width: 25%'>".$row['name']."</td>";
				$table .= "<td style='width: 25%'>".$row['email']."</td>";
				$table .= "<td style='width: 25%'>".$row['work_phone']."</td>";
				$table .= "<td style='width: 25%'>".$row['mobile_phone']."</td></tr>";
			}
			$table .= '</tbody></table>';
		}
		else {
			$table = 'There are no contacts for this job';
		}
		
		$object = (object) [
			'masterstatus' => 'success',
			'table' => $table,
		];
		echo json_encode($object);
	}
}

// Using 'jobid' and 'elements' array containing 'jobDate', 'notename', and 'note', add job note to database
function addJobNote($dataObj) {
	require_once '../classes/Validation.php';
	require_once '../classes/Pdo_methods.php';
	require_once '../classes/General.php';
	
	$validate = new Validation();
	$error = false;
	$count = count($dataObj->elements);
	
	// Check for field errors
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

		// Create bindings for job note
		$elementNames = array('jobDate^^str', 'notename^^str', 'note^^str');
		$bindings = $general->createBindedArray($elementNames, $dataObj);
		array_push($bindings, array(':jobId', $dataObj->jobid, 'int'));

		$sql = "INSERT INTO job_note (job_id, note_date, note_name, note) VALUES (:jobId, :jobDate, :notename, :note)";
					
		$result = $pdo->otherBinded($sql, $bindings);
		if($result = 'noerror'){
			$response = (object) [
				'masterstatus' => 'success',
				'msg' => 'The job note has been created',
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

// Using 'jobid', echo object with 'table' with html for the job note table
function viewJobNotes($dataObj) {
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, note_date, note_name, note FROM job_note WHERE job_id = :jobId";
	$bindings = array(
		array(':jobId', $dataObj->jobid, "int")
		);
	$records = $pdo->selectBinded($sql, $bindings);
	if($records == 'error'){
		$response = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with our sql statement',
		];
		echo json_encode($response);
    }
    else {
		$notes = '';
		// Create the job notes table
    	if(count($records) != 0){
        	$notes = '<table class="table table-bordered table-striped" id="noteTable">';
        	$notes .= '<thead><tr><th>Date</th><th>Note Name</th><th>Note</th><th>Update</th><th>Delete</th></tr></thead><tbody>';

        	foreach ($records as $row) {
				$date = date('Y-m-d', floor($row['note_date'] / 1000));

				$notes .= "<tr><td style='width: 10%'>".$date."</td>";
				$notes .= "<td style='width: 10%'>".$row['note_name']."</td>";
				$notes .= "<td style='width: 60%'>".$row['note']."</td>";
				$notes .= "<td style='width: 10%'><input type='button' class='btn btn-success' value='Update' id='".$row['id']."'></td>";
        		$notes .= "<td style='width: 10%'><input type='button' class='btn btn-danger' value='Delete' id='".$row['id']."'></td></tr>";
        	}
        	$notes .= '</tbody></table>';
	    }
	    else {
	    	$notes = 'No notes found for this job';
		}
		$response = (object) [
			'masterstatus' => 'success',
			'table' => $notes,
		];
		echo json_encode($response);
    }
}

// Using 'id', remove the job note from the database
function deleteNote($dataObj) {
	require '../classes/Pdo_methods.php';
	
	$pdo = new PdoMethods();	
	
	$sql = "DELETE FROM job_note WHERE id = :id";
	$bindings = array(
		array(':id', $dataObj->id, 'int'),
	);
	
	$result = $pdo->otherBinded($sql, $bindings);
	
	if($result = 'noerror'){
		$object = (object) [
			'masterstatus' => 'success',
			'msg' => 'Note deleted'
		];
		echo json_encode($object);
	}
	else {
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Could not delete note'
			];
		echo json_encode($object);
	}
}

// Using 'noteId', echo object with 'form' with html for the update note form
function updateNoteForm($dataObj) {
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, note_date, note_name, note FROM job_note WHERE id = :id";
	$bindings = array(
		array(':id', $dataObj->noteId, "int")
		);
	$records = $pdo->selectBinded($sql, $bindings);
	if($records == 'error'){
		$response = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with our sql statement',
		];
		echo json_encode($response);
    }
    else {
		// Divide by 1000 to convert from milliseconds
		$date = date('Y-m-d', floor($records[0]['note_date'] / 1000));
		// Update jote note form html using heredoc
		$form = <<<HTML
		<div id="updatejobnoteform" class="form">
		<div class="row">
			<div class="col-md-12">
			<div class="form-group">
				<label for="jobDate">Date:</label>
				<input type="date" class="form-control" id="jobDate" name="date" value="$date">
			</div>
			</div>
		</div>
		<div class="row">    
			<div class="col-md-12">
			<div class="form-group">
			<label for="notename">Note Title:</label>
			<input type="text" class="form-control" id="notename" value="{$records[0]['note_name']}">
			</div>
		</div>
		</div>
		<div class="row">
		<div class="col-md-12">
			<div class="form-group">
			<label for="note">Note:</label>
			<textarea name="note" id="note" class="form-control">{$records[0]['note']}</textarea>
			</div>
		</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
				<input type="button" class="btn btn-success" name="{$records[0]['id']}" id="updatejobnoteBtn" value="Update Job Note">
				</div>
			</div>
			</div>
		</div>
HTML;
		$response = (object) [
			'masterstatus' => 'success',
			'form' => $form,
		];
		echo json_encode($response);
	}
}

// Using 'noteId', and elements array with 'jobDate', 'notename', and 'note', update the note in the database
function updateNote($dataObj) {
	require_once '../classes/Validation.php';
	require_once '../classes/Pdo_methods.php';
	require_once '../classes/General.php';
	
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

		// Create bindings for job note
		$elementNames = array('jobDate^^str', 'notename^^str', 'note^^str');
		$bindings = $general->createBindedArray($elementNames, $dataObj);
		array_push($bindings, array(':noteId', $dataObj->noteId, 'int'));

		$sql = "UPDATE job_note SET note_date = :jobDate, note_name = :notename, note = :note WHERE id = :noteId";
					
		$result = $pdo->otherBinded($sql, $bindings);
		if($result = 'noerror'){
			$response = (object) [
				'masterstatus' => 'success',
				'msg' => 'The job note has been updated',
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

// Using 'jobId' and elements array containing 'jobDate', 'hours', 'hourlyRate', 'description', add the job hours to the database
function addHours($dataObj) {
	require_once '../classes/Validation.php';
	require_once '../classes/Pdo_methods.php';
	require_once '../classes/General.php';
	
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

		// Create bindings for job hours
		$elementNames = array('jobDate^^str', 'hours^^str', 'hourlyRate^^int', 'description^^str');
		$bindings = $general->createBindedArray($elementNames, $dataObj);
		array_push($bindings, array(':jobId', $dataObj->jobId, 'int'));

		$sql = "INSERT INTO job_hour (job_id, job_date, job_hours, hourly_rate, description) VALUES (:jobId, :jobDate, :hours, :hourlyRate, :description)";
					
		$result = $pdo->otherBinded($sql, $bindings);
		if($result = 'noerror'){
			$response = (object) [
				'masterstatus' => 'success',
				'msg' => 'The job hours have been added',
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

// Using 'jobId', echo object with 'table' that contains html for job hours table
function getJobHours($dataObj) {
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, job_date, job_hours, hourly_rate, description FROM job_hour WHERE job_id = :jobId";
	$bindings = array(
		array(':jobId', $dataObj->jobId, "int")
		);
	$records = $pdo->selectBinded($sql, $bindings);
	if($records == 'error'){
		$response = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with our sql statement',
		];
		echo json_encode($response);
    }
    else {
		$hours = '';
    	if(count($records) != 0){
        	$hours = '<table class="table table-bordered table-striped" id="hoursTable">';
        	$hours .= '<thead><tr><th>Date</th><th>Hours</th><th>Rate</th><th>Description</th><th>Update</th><th>Delete</th></tr></thead><tbody>';

        	foreach ($records as $row) {
				$date = date('Y-m-d', floor($row['job_date'] / 1000));

				$hours .= "<tr><td style='width: 10%'>".$date."</td>";
				$hours .= "<td style='width: 10%'>".$row['job_hours']."</td>";
				$hours .= "<td style='width: 10%'>".$row['hourly_rate']."</td>";
				$hours .= "<td style='width: 50%'>".$row['description']."</td>";
				$hours .= "<td style='width: 10%'><input type='button' class='btn btn-success' value='Update' id='".$row['id']."'></td>";
        		$hours .= "<td style='width: 10%'><input type='button' class='btn btn-danger' value='Delete' id='".$row['id']."'></td></tr>";
        	}
        	$hours .= '</tbody></table>';
	    }
	    else {
	    	$hours = 'No hours found for this job';
		}
		$response = (object) [
			'masterstatus' => 'success',
			'table' => $hours,
		];
		echo json_encode($response);
    }
}

// Using 'hourId', delete the job hours from the database
function deleteHours($dataObj) {
	require '../classes/Pdo_methods.php';
	
	$pdo = new PdoMethods();	
	
	$sql = "DELETE FROM job_hour WHERE id = :id";
	$bindings = array(
		array(':id', $dataObj->hourId, 'int'),
	);
	
	$result = $pdo->otherBinded($sql, $bindings);
	
	if($result = 'noerror'){
		$object = (object) [
			'masterstatus' => 'success',
			'msg' => 'Hours deleted'
		];
		echo json_encode($object);
	}
	else {
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Could not delete hours'
			];
		echo json_encode($object);
	}
}

// Using 'hourId', echo an object with 'form' containing html for the update hours form populated with data from the database
function getHoursUpdateForm($dataObj) {
	require '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, job_date, job_hours, hourly_rate, description FROM job_hour WHERE id = :id";
	$bindings = array(
		array(':id', $dataObj->hourId, "int")
		);
	$records = $pdo->selectBinded($sql, $bindings);
	if($records == 'error'){
		$response = (object) [
			'masterstatus' => 'error',
			'msg' => 'There was an error with our sql statement',
		];
		echo json_encode($response);
    }
    else {
		// Divide by 1000 to convert from milliseconds
		$date = date('Y-m-d', floor($records[0]['job_date'] / 1000));
		// Updates hours form html in heredoc
		$form = <<<HTML
		<div id="updateHoursForm" class="form">
			<div class="row">
				<div class="col-md-12">
				<div class="form-group">
					<label for="jobDate">Date:</label>
					<input type="date" class="form-control" id="jobDate" name="date" value="$date">
				</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
				<div class="form-group">
					<label for="hours">Hours:</label>
					<input type="text" class="form-control" name="hours" id="hours" value="{$records[0]['job_hours']}">
				</div>
				</div>
				<div class="col-md-6">
				<div class="form-group">
					<label for="hourlyRate">Hourly Rate:</label>
					<input type="text" class="form-control" name="hourlyRate" id="hourlyRate" value="{$records[0]['hourly_rate']}">
				</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
				<div class="form-group">
					<label for="description">Description:</label><br />
					<textarea rows="10" cols="10" id="description" class="form-control">{$records[0]['description']}</textarea>
				</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
				<input type="button" class="btn btn-success" value="Update Hours" id="updatejobhoursBtn">
				</div>
			</div> 
		</div>
HTML;
		$response = (object) [
			'masterstatus' => 'success',
			'form' => $form,
		];
		echo json_encode($response);
	}
}

// Using 'hourId' and elements array containing 'jobDate', 'hours', 'hourlyRate', 'description', update the job hours in the database
function updateHours($dataObj) {
	require_once '../classes/Validation.php';
	require_once '../classes/Pdo_methods.php';
	require_once '../classes/General.php';
	
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

		// Create bindings for job hours
		$elementNames = array('jobDate^^str', 'hours^^str', 'hourlyRate^^int', 'description^^str');
		$bindings = $general->createBindedArray($elementNames, $dataObj);
		array_push($bindings, array(':hourId', $dataObj->hourId, 'int'));

		$sql = "UPDATE job_hour SET job_date = :jobDate, job_hours = :hours, hourly_rate = :hourlyRate, description = :description WHERE id = :hourId";
					
		$result = $pdo->otherBinded($sql, $bindings);
		if($result = 'noerror'){
			$response = (object) [
				'masterstatus' => 'success',
				'msg' => 'The job hours have been updated',
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

// With 'jobId', and elements array with 'name' and 'file', and _FILES array, add the file to the job's folder and add the path to the database
function addAsset($dataObj, $file) {
	require '../classes/Validation.php';
	$Validation = new Validation();
	$error = false;

	if(!$Validation->validate($dataObj->elements[0]->regex, $dataObj->elements[0]->value)){
		$dataObj->masterstatus = 'fielderrors';
		$dataObj->elements[0]->status = 'error';
		$error = true;
	}

	// Check if no file
	if(empty($_FILES)) {
        $dataObj->masterstatus = 'fielderrors';
        $dataObj->elements[1]->msg = 'You must select a file';
        $dataObj->elements[1]->status = 'error';
        $error = true;
	} 
	
	if($error){
		echo json_encode($dataObj);
        return;
	}

	$filename = $_FILES['file']['name'];
	$filesize = $_FILES['file']['size'];
	$filetype = $_FILES['file']['type'];
	$filetempname = $_FILES['file']['tmp_name'];

	/* CHECK FILE SIZE AND TYPE */
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (finfo_file($finfo, $filetempname) !== "application/pdf"){
    	finfo_close($finfo);
    	$dataObj->masterstatus = 'fielderrors';
    	$dataObj->elements[1]->msg = 'File is wrong type';
        $dataObj->elements[1]->status = 'error';
        echo json_encode($dataObj);
    }
    else if ($filesize > 1000000){
    	$dataObj->masterstatus = 'fielderrors';
    	$dataObj->elements[1]->msg = 'File size is too big';
        $dataObj->elements[1]->status = 'error';
        echo json_encode($dataObj);
    }
    /* IF ALL IS GOOD THEN ADD FILE AND UPDATE DATABASE */
    else{
		require_once '../classes/Pdo_methods.php';
		$pdo = new PdoMethods();

		/* GET THE FOLDER PATH FROM THE JOB DATABASE */
		$sql = "SELECT folder FROM job WHERE id = :jobId";

		$bindings = array(
			array(':jobId',$dataObj->jobId,'int'),
		);

		$records = $pdo->selectBinded($sql, $bindings);

		$folder = $records[0]['folder'];

		/* REMOVE ALL SPACES FROM THE FILE NAME AND ADD UNDERSCORES */
		$filename = str_replace(" ","_",$filename);
		$path = $folder."/".$filename;

		// Check if file already exists on server
		// Otherwise two database records can point to the same file
		if (file_exists($path)) {
			$dataObj->masterstatus = 'fielderrors';
	    	$dataObj->elements[1]->msg = 'File already exists on the server';
	        $dataObj->elements[1]->status = 'error';
	        echo json_encode($dataObj);
			exit;
		}

		if(!move_uploaded_file($filetempname, $path)){
			$dataObj->masterstatus = 'fielderrors';
	    	$dataObj->elements[1]->msg = 'There was an problem with the file';
	        $dataObj->elements[1]->status = 'error';
	        echo json_encode($dataObj);
			exit;
		}

		$sql = "INSERT INTO job_asset (job_id, name, file) VALUES (:jobId, :name, :file)";

		$bindings = array(
			array(':jobId',$dataObj->jobId,'int'),
			array(':name',$dataObj->elements[0]->value,'str'),
			array(':file',$path,'str')
		);

		$result = $pdo->otherBinded($sql, $bindings);

		if($result == 'noerror'){
			$object = (object) [
				'masterstatus' => 'success',
				'msg' => 'Asset has been added'
			];
			echo json_encode($object);
			
		}
		else {
			$object = (object) [
				'masterstatus' => 'error',
				'msg' => 'There was an error adding the asset'
			];
			echo json_encode($object);
		}
	}
}

// Using 'jobId', echo object with 'table' with html for job asset table
function viewDeleteAsset($dataObj) {
	require_once '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();

	$sql = "SELECT id, name, file FROM job_asset WHERE job_id = :jobId";

	$bindings = array(
		array(':jobId',$dataObj->jobId,'int'),
	);

	$records = $pdo->selectBinded($sql, $bindings);

	if(count($records) == 0){
		$response = (object) [
			'masterstatus' => 'error',
			'msg' => 'There are no assets for this job',
		];
		echo json_encode($response);
	}
	else{
		// Delete job asset table
		$table = '<table class="table table-bordered table-striped" id="jobAssetTable"><thead><tr><th>Name</th><th>Delete</th></tr></thead><tbody>';

		foreach($records as $row){
			$table .= '<tr><td style="width: 80%"><a href="'.$row['file'].'">'.$row['name'].'</a></td>';
			$table .= '<td style="width: 20%"><input type="button" class="btn btn-danger" id="'.$row['id'].'" value="Delete"></td></tr>';
		}

		$table .= '</table>';

		$response = (object) [
			'masterstatus' => 'success',
			'table' => $table,
		];
		echo json_encode($response);
	}

}

// Using 'assetId', delete the asset file and delete the record from the database
function delAsset($dataObj) {
	require_once '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();

	$sql = "SELECT file FROM job_asset WHERE id = :assetId";

	$bindings = array(
		array(':assetId',$dataObj->assetId,'int'),
	);

	$records = $pdo->selectBinded($sql, $bindings);

	$filepath = $records[0]['file'];

	// Delete the file from the server
	if(!unlink($filepath)){
		$object = (object) [
			'masterstatus' => 'error',
			'msg' => 'Could not delete file'
		];
		echo json_encode($object);
		exit;
	}

	// Delete the asset from the database
	$sql = "DELETE FROM job_asset WHERE id=:assetId";

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

?> 