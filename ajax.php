<?php
//
//This file implements a mecahanism for the client to request a service from
//the server without any user intervention, i.e., without opening any window.
//For example, delete record()
//
//The service is specified as (new $classname($data))->$method where:-
//$classname is the name of the object class to create
//$data is the posted global data
//$method is the specific function to execute on the object
//
//The output from serving this file is buffered by default, i.e, will be sent to 
//the client when the job is done, but the user can switch it off where 
//responsibness is critical. The most notable case is scrolling
//
//Include the core mutall library and all extensions needed to suport the
//broad user interface services (BUIS).
require_once 'buis.php';
//
//Create a object that will allow us to access the core mutall functions, e.g., 
//access to a specific global variable that is used for passing data between
//pages
$mutall = new mutall();
//    
//Catch any errors; this is important particularly when the error reporting on 
//a remote server is not available. I guess for security reasons. Here we take 
//care of them.   
try {
    //
    //The ajax method expects the client to post tHe query string
    //Posting is safer (so that users cannot see the password for exceuting 
    //this page) and has no limits to the data size
    //
    //Retrieve $_POST variable indirectly to avoid the warning about access to global 
    //variables
    $qstring = querystring::create(INPUT_POST);
    //
    //Set the querystring array
    $arr = $qstring->arr;
    //
    //Retrieve the class name of the page object we need to create.
    $classname = $arr['classname'];
    //
    //Get the method we need to execute on the object
    $method= $arr['method'];
    //
    //Create the new page object using the posted query string. Pass it the 
    //query string objet
    $obj= new $classname($qstring);
    
    //
    //Execute the requested methos taking care of those cases that 
    //should and should not be buffered. Buffering is needed where we expec
    //the output to be much more than the html gerenared by the method. It 
    //allows us to return to values returned by exceuting the method as extra 
    //data
    //
    //If the expected output is html, then execute the request unconditionally,
    //i.e, without buffering. The default output for ajax calls is a json string.
    //That requires buffering
    if (isset($arr['expected_output']) && $arr['expected_output']==="html"){
        //
        //Execute the function with no arguments; if there is a runtime error the 
        //outer catch will handle it. For the non-buffered case the result is 
        //immaterial -- any resulting error will be visible, hopefully, in the 
        //output.
        $obj->$method();
        //
        return;
    }
    //
    //Bufferring is required; prepare to collect the output result
    //
    //Define the result object for packaging the output if the ajax method 
    //was requested by the client. It is defined here as it is required 
    //whether we trap any errors or not.
    $result = new stdClass;
    //
    //We need to determine when we have successfully exceuted the request 
    //and when not. 
    try {
        //
        //Start buffering the output
        ob_start();
        //
        //Execute the requested function and consider it's returned value as the 
        //extra data. Note that that it is allowed to call the method without
        //any parameters because they are all optional
        $extra = $obj->$method();
        //
        //This is a successful operation; indicate so.
        $result->status = "ok";
        //
        //At this point collect all the buffered output as the html, stop
        //the buffering and clean the buffer.
        $result->html= ob_get_clean();
        //
        //Add the returned value of the execution, as the extra data, to the 
        //html output
        $result->extra = $extra;
        //
        //Send the complete result to the client
        echo json_encode($result);

    } catch (Exception $ex) {
        //
        //Get the detailed error message
        $msg = $mutall->get_error($ex);
        //
        //Discard what was in the buffer and stop bufferring
        ob_end_clean();
        //
        //Return the result as an error message
        $result->status="error";
        //
       //Compile the full error message and assign it to the result variable
        $result->html = $msg;
        //
        //Return the results to the client and stop the procssing
        die(json_encode($result));
    }
}
catch(Exception $ex){
    //
    //Return this error to the client, and stop the processing
    die($mutall->get_error($ex));
    
}
