<?php
//
//The library of core mutall PHP classes

//Start a mutall session if not yest
if (session_status()===PHP_SESSION_NONE){
    session_start();
}

//This class is extended by all mutall objects, i.e., its the root of all 
//mutall -compatible classes. The key property is classname which is used
//for activation purposes
class mutall{
    //
    //All mutall objects support the classname property
    public $classname;
    //
    //The default database associated with this MUTALL object is set from 
    //php global variables
    public $dbase;
    //
    //The index last page saved to the global session variable
    const last_page = "last_page";
    //saved
    //The constant for accessing the mutall data in the global variables
    const id = "mutall";
    //
    //The following variables, layout_typ, mode_type and criteria are defined for
    //all mutall objects so that querystring and page objects, which need them,
    //can access them.
    //
    //The file name (without extension) of the page being served. This name is
    //used for naming variables that are shared between js and php
    public $page_filename;
    
    
    //Construct the mutall object
    function __construct(){
        //
        //All mutall objects have a classame that allows a jvascript or php
        //application to activate it using a mutalll object for bootstrapping
        //purposes
        $this->classname = get_class($this);
        //
        //Set the name of the page that is being served. (It is not __FILE__ !)
        $path = $_SERVER['SCRIPT_FILENAME'];
        $this->page_filename = pathinfo($path, PATHINFO_FILENAME);
        //
        //Set an error handler that converts all php notices, warnings, errors, etc
        //into exceptions.
        set_error_handler(function($errno, $message, $filename, $lineno, array $errcontext) {
            //
            //If the error was suppressed with the @-operator...
            if (0 === error_reporting()) {
                //
                //..then return false
                return false;
            }
            //
            //Convert the php notice, warning or error into an exception
            throw new ErrorException($message, 0, $errno, $filename, $lineno);
        });
    }
    
    //The string version of a mutall object returns a checked json string. It is
    //useful for interfacing js with php
    function __toString(){
        //
        //Convert this mutall object into a checked json string
        return $this->stringify();
    }
    
    //Return the ok message to the client via the ajax mechanism
    function ok(){
        //
        //To avoid noise, do a fresh object -- rather than pass this one
        $obj= new stdClass();
        //
        //Set the ststus to ok
        $obj->status = "ok";
        //
        //There is no html data and no extra
        $obj->html= "";
        $obj->extra="";
        //
        $output = json_encode($obj);
        //
        //Echo the json version of the output
        //echo $output;
        echo $output ;
    }
    
    //
    //Search the global variables for the posted *static) mutall data in this order:
    //$_POST, $_GET and $_SESSION. Return false if none. The cache is used for
    //efficiency
    function get_posted_data(){
        //
        //Start by checking the casch
        if (isset($this->global_data)){
            return $this->global_data;
        }
        //Ensure that the returned values from here are all json_decoded, i.e,
        //converted to php stdClasses;
        //
        //The data comes from a get querystring 
        if (isset($_GET[mutall::id])){
            //
            $str = $_GET[mutall::id];
        }
        //The data comes from a posting
        elseif (isset($_POST[mutall::id])){
            $str = $_POST[mutall::id];
        }
        //
        //The data comes from a session variable
        elseif (isset($_SESSION[mutall::id])){
            $str = $_SESSION[mutall::id];
        }
        //
        //...otherwise there is no data; return false
        else {
            return false;
        }
        //
        //Convert the str to a proper php by doing a checked json_encode
        $php = json_decode($str);
        //
        //If the php is null there must have been a decoding error. Report it
        if (is_null($php)){
            die("Json decoding error: "
                    . json_last_error_msg()
                    . "<br/> Occcued in page ".$this->page_filename);
        }
        //
        //Cache the global data and return it
        $this->global_data = $php; 
        //
        return $php;
    }
    
    //Returns true if a property is found in any of the the given variable
    //under key mutall::id
    function try_property($source, $name){

        //
        //Check if the data ia available
        if (!isset($source[mutall::id])){
            return false;
        }
        //Check if the property  exists
        $data = json_decode($source[mutall::id]);
        //
        if (!isset($data->$name)){
             return false;
        }
        //
        //return the data
        return $data->$name;
    }
        
    //Retrieve the requested data from any of the global variables searching 
    //in this order: $_GET, $_POST and $_SESSION. It throws an exception if the
    //requested data cannot be found
    function get_property($name){
        //
        //List all the global variables to search, starting from this object
        $sources = [$_GET, $_POST, $_SESSION];
        //
        //Visit each of the global variables
        foreach($sources as $source){
            //
            $value = $this->try_property($source, $name);
            //
            //Return the value if it is valid. (An empty string is false!!)
            if ($value || $value===""){
                //
                return $value; 
            }
        }
        //
        //Request not met
        throw new Exception("No data found for property '$name'");
        //
        
    }
    
    //Execute the a function (module) of this mutall object. It nust be set
    function execute(){
        //
        //Get the global data
        $data = $this->get_posted_data();
        //
        //If the module is not set, set it to "show"
        $module = (isset($data->module)) ? $data->module : "show";
        //
        //Catch ane remport eny execution errors
        try {
            //
            //Execute it. How would you test if the function is implemented?
            echo $this->$module();
        } catch (Exception $ex) {
            //
            echo $ex->getMessage().". ".$ex->getTraceAsString();
        }
        
    }
    //Report the given error -- including the page where it ocurred; then die
    function error($msg){
        //
        die($msg."<br/> Error occured in page ".$this->page_filename);
    }
        
    
    //Returns the best the database connection. It may be /immediately available; 
    //if not then create a new one using the available login credentials 
    function get_dbase(){
        //
        //If the dbase property is set, return it; otherwise continue to 
        //establish it from user log in credentials
        if (isset($this->dbase)){
            //
            //Test if we need to activate it or not
            if (get_class($this->dbase)==="stdClass"){
                //
                //Yes we do. Activate it/
                $this->dbase = $this->activate($this->dbase);
            }
            //
            //Return the actuve form
            return $this->dbase;
        }
        //
        //Define the login variable
        $login=null;
        //
        //Get the login credentials; there must be some,otherwise an exception 
        //will be thrown
        $this->get_login($login);
        //
        //The database name must be a valid property of this object; otherwise 
        //you geta runtime error.
        $dbname= $this->dbname;
        //
        //Create a new database connction
        $this->dbase = new dbase($login['username'], $login['password'], $dbname);
        //
        //Return the connected database
        return $this->dbase;
    }
    
    
    //Open a mutall_data database using the login credentials of an administrator
    //(rather than those of the user because this opertaion is a BUIS process)
    function get_mutall_dbase(){
        //
        return new dbase(page_login::username, page_login::password, page_login::mutall_data);
    }
    
    //This method tries to convert any stdClass (note the underbar) with the 
    //classname property to an mutall object of that named class -- thus 
    //activating it. If the input is ommited, the data in one of the the 
    //global variables in this order: $_POST, $_GET and $_SESSSION. If such 
    //data is not available, it returns false
    function try_activate($input1_=null){
        //
        //Try to get the activation data
        $input_ = $this->try_activation_data($input1_);
        //
        //See if you have the activation data available
        if (!$input_){
            //
            //None is available; return false
            return false;
        }
        //
        //Otherwise activate the input
        return $this->activate($input_);
    }
    
    //This method to converts the given static data (note the underbar) with the 
    //classname property to an mutall object of that named class -- thus 
    //activating it.
    function activate($input_){
        //
        //Classify the input to either mutall, object, array or other
        $type = $this->classify($input_);
        //
        switch($type){
            //
            //mutall objects have a classname
            case "mutall":
                //
                //Avoid endless looping for a class called mutall
                if ($input_->classname==="mutall"){
                    $input = $this;
                }
                //
                //For all other classes, activate them
                else{
                    $input = $this->activate_class($input_->classname, $input_);
                    //
                    //Append to the active object the rest of the properties
                    foreach($input_ as $property=>$static_value){
                        //
                        //Activate the static value and set the corresponding 
                        //property if not yet set by the constructor (or it is null)
                        if (!isset($input->$property) || is_null($input->$property)){
                            //
                            $input->$property = $this->activate($static_value);
                        }
                    }    
                }
                break;
            //
            //For an ordinary object offload all the properties defined by the 
            //input unconditionally    
            case "object":
                //
                $input = new stdClass;
                //
                foreach($input_ as $property=>$static_value){
                    //
                    //Activate the static value and set teh corresponding property
                    //on the active object
                    $input->$property = $this->activate($static_value);
                }
                break;
            //
            //For the array activate all the components
            case "array":
                //
                $input = [];
                //
                //Visit all the elements of the array
                foreach($input_ as $property=>$static_value){
                    //
                    //Activate teh static value
                    $value = $this->activate($static_value);
                    //
                    //Set the property
                    $input[$property] = $value;
                }       
                break;
            //
            //Any other structure is returned as it is
            default:
                $input = $input_;
        }
        //
        //Return the active object
        return $input;
    }
   
    
    //Classify the given object as either mutall, object, array or other    
    function classify($input_){
        //Find out if this is an array or o=not
        if (is_array($input_)){
            return "array";
        }
        //
        //Determin if this is an ordinary object or a mutall class
        if (is_object($input_)){
           //
            if (isset($input_->classname)){
                return "mutall"; 
            }
            //
            return "object";
        }    
        //
        //Return any other type
        return "other";
    }
   
    //Report exceptions in a more friendly fashion
    function report_error($ex){
        //
        //Replace the hash with a line break in teh terace message
        $trace = str_replace("#", "<br/>", $ex->getTraceAsString());
        //
        //Retirn the full message
        return $ex->getMessage()."<br/>$trace";
    }
    
    //Save the current page to the global session variable. This is achived by 
    //saving the given query string (text) to th last page sesion variable
    function save_to_session($qstring=null){
        //
        //Bind teh querystring variable to the actual query string in teh posted
        //data
        $this->bind_arg('querystring', $qstring);
        //
        //Save it to the lastpage session variable, overrrining whatever was 
        //there
        $_SESSION[mutall::lastpage] = $qstring;
    }
    
     //Returns the true if the login credentials can be found in session variables. 
    //The credentails are bound to teh reference variable.
    //It fails with an exception if the user is not logged
    function get_login(&$login){
        //
        if (!$this->try_login($login)){
            throw new Exception("Login credentials not found.");
        }
    }
    
    //Returns true if the login credentials are available; they are bound to the
    //given variable
    function try_login(&$login){
        //
        //See if login credentals are available
        if (isset($_SESSION['login'])){
            //
            $login = $_SESSION['login'];
            return true;
        }
        return false;
    }
    
    
      
    //Returns a more friendly fassion of the exception error. This is important
    //when the remote server's error reporting has been switched off.
    function get_error($ex){
        //
        //Replace the hash with a line break in teh terace message
        $trace = str_replace("#", "<br/>", $ex->getTraceAsString());
        //
        //Retirn the full message
        return $ex->getMessage()."<br/>$trace";
    }
    
    //An checked json encoding function.
    //Compile the given context to a json string and check the
    //conversion results for recursion. The conversion exits on error.
    //You beed to inspect the appropriate script tag to see where
    //unprotected resursion occured
    function stringify(){
        //
        //Call the (default) unchecked json encoder
        $json = json_encode($this);
        //
        //Test whether the encoding was sucsessful or not; dying if not
        if (!$json)
        {
            //Show the json message
           echo(json_last_error_msg());
            //
           //Echo the jsoned structure to show error points. What is the true
           //parameter for? Preserving formatting space? How does this relate to the 
           //<pre> tag?
           die(print_r($this, true));
        }
        //
        //Return the encoded strimg
        return $json;
    }
    
    //Offload properties from the given static input to this mutall object with
    //an option for activating them or not. Under what circumstaces is activation
    //necessary? All the indications are that we it should not be done, 
    //particulary for pages that re-calls themselves. In the case of a simple 
    //page_record refresh it caused a re-build of page_record (which is not
    //a trivial page) and slowed down process. Hence the action was switched off
    function offload_properties($input, $activate=null){
        //
        //Proceed only if the input is an object. (What about an array?)
        if (!is_object($input)) {return; }
        //
        //The input is an object. Offfload its key/value pairs
        foreach($input as $key=>$value){
            //
            //Ignore this property if it is not already set (by the 
            //constructor) or if the set value is null
            if (!isset($this->$key) || is_null($this->$key)){
                //
                //Set the property -- activating it if necessaty
                $this->$key = is_null($activate) ? $value : $this->activate($value);
            }

        }
    }
   
    //Go to the named php page; its the equivalent of open_window in js
    function open_window($name=null){
        //
        //Use the given name as the filename if it is valid; otherwise use the 
        //classname of this mutall object as the page name with a php file extension
        $filename = is_null($name) ? "$this->classname.php": $name;
        //
        //The file must exist
        if (!file_exists($filename)){
            //
            throw new Excecemption("File named $filename does not exist");
        }
        //
        //Use the fileame to redirect to another page
        header("Location:".$filename);
    }
    
    //Activate the registered class
    function activate_class($classname, $obj){
        //
        //Create the registered class object -- depending on the class name. 
        //Classes that are constructed with more have more need to be 
        //regietered explicitly. Those without uses the default initializer
        switch($classname){
            //
            //An ordinary column needs no specilaization
            case "column":
                $tname = $obj->tname;
                $name = $obj->name;
                $class = new column($tname, $name, $obj);
                break;
            //
            case "column_primary":
                $tname = $obj->tname;
                $name = $obj->name;
                $class = new column_primary($tname, $name, $obj);
                break;
            //    
            case "column_foreign":
                $tname = $obj->tname;
                $name = $obj->name;
                $foreign=$obj->foreign;
                $class = new column_foreign($tname, $name, $obj, $foreign);
                break;
            //
            case "dbase":
                $username = $obj->username;
                $password = $obj->password;
                $dbname = $obj->dbname;
                $class = new dbase($username, $password, $dbname);
                break;
            //
            //A text expression must have a text property that is used for 
            //instantiating a text expressin
            case "expression_text":
                //
                //Get the  text property
                $text= $obj->text;
                //
                //Instantiate the text expression
                $class = new expression_text($text);
                break;
            //
            case "record":
                $fields = $this->activate($obj->fields);
                $dbase = $this->activate($obj->dbase);
                //
                //Leave out the optional tname; it will be added to the active 
                //record anyway.
                $class = new record($fields, $dbase, $obj->tname, $obj->reftable, $obj->stmt, $obj->values);
                break;
            //      
            //Otherwise use the general initializer
            default:
                //Test if the class exists in this file
                if (class_exists($obj->classname)){
                    //
                    //This is not guranteeed to always work; it wil fail if the
                    //constructor has madtory parameters. So, trap it.
                    $class = new $obj->classname();
                }
                //
                //Otherwise throw an exception
                else{
                    throw new Exception("This class '$obj->classname' is not registered in the mutall system");
                }
        }
        //
        //Return theh class
        return $class;
    }
}

//The base class of all mutall pages; it assumes that teh drive is an sql. 
//This is overiddable.
class page extends querystring{
    //
    //The data, i.e., sql or record, that drives this page's display. Lets 
    //force this property to be accessed via a __GETTER__  method by 
    //protecting it. This means that the driver cannot be accesed by javascript.
    //Thats ok because it carries no primary data. (AFTERTHOUGHT. NO.THAT IS NOT
    //CORRECT. IT CARRIES FIELDS!! WHICH ARE BADLY NEEDED IN JS). The reason 
    //for the magic getter is so that this prOperty will be accessed only when 
    //needed -- not when constructing the page. Then all the properties of the 
    //page being constructed will be available -- thus alowing us the current practice of
    //construcing the parent class before the extended one. (Revert back to 
    //public)
    public $driver;
    //
    //A page index is the name of a field that supplies data to the id attribute
    //of the dom records displayed by this page.
    //The index of an sql is used for:- 
    //(a) formulating the id attribute of a dom record so that we can hreference
        //to that specicic record. (This is partcularly important for the 
        //sql in a selector page)
    //(b) setting the index name property to the id value in (a) when a user
        //selects a dom field
    //The index links a database record to the visual dom a version
    public $index;
    //
    //The javascript expression that is associated with this page. By default
    //the expression has the same name as that of the page. For a dscendant page
    //it is an expression that evaluates to a descendant. This expression is used
    //for contextualizing user actions or events associated with this page. For 
    //instance, the onlick event in a descendant page is qualified as
    //onclick = "page_record.descendants.client.onclick()" where the js 
    //expression is "page_record.descendants.client".
    public $jsxp;
    //
    //The cascaded style sheet (css) expression is used for describing the 
    //position (element-wise) of this page -- so that the css expresion can be 
    //used for loacting this page (in a complex document). This expression 
    //is needed to support refreshng of the page related section in an ajax
    //operation. The dafeult it the "article" element
    public $cssxp = "article";
    //
    //The query string used for construction this page
    //This is useful for saving this page to a session
    public $qstring;
    //
    //The default mode of a page is output
    public $mode_type = mode::output;
    //
    //The default display layout is tabular
    public $layout_type = layout::tabular;
    //
    //Indicates if we should output the header of some table or not.The default 
    //is false, meaning that the headers are needed.
    public $body_only=false;
    //
    //Whicn version of buis are we running? This is needed to inform 
    //the javascriot ajax and open_window methods the folder to search to
    //the files -- as they may not be located in the root of the server
    public $version;
    //
    //The querystring of a page is mandatory. Note that all the default values
    //are simple data types because they must be ingerred from the querystring
    function __construct(querystring $qstring, $dbname=null, $layout_type=null, $mode_type=null, $jsxp=null){
        //
        //
        //Initialize the parent query string system so that we can access basic 
        //functions for implementing this constructor
        parent::__construct($qstring->arr);
        //
        //Hoping that the version is set...
        $this->version= "../".pathinfo(__DIR__,PATHINFO_BASENAME);
        //
        //Bind the optional argument; this is done after initializing the
        //parent so that we have full access to the srvievs defiend on the 
        //mutall object. The predefined default means that the binding can nver
        //fail.
        //
        //Not all pages need to be associated with a database name; hence this 
        //is optional
        if ($this->try_bind_arg('dbname', $dbname)){
            //
            $this->dbname = $dbname;
        }
        //
        //Bind the page style
        $this->bind_arg('layout_type', $layout_type);
        $this->bind_arg('mode_type', $mode_type);
        //
        //The default value of javascript epression for this page is the same 
        //name the class of as this page; this is the justification for the 
        //global js variable to be named the same as this page.
        if ($this->try_bind_arg('jsxp', $jsxp)){
           //
           $this->jsxp = $jsxp;
        }
        else{
           //
           $this->jsxp = get_class($this);
        }
        //
        //Bind the style objects, viz., layout and mode
        //
        //Set the layout; this can never fail because of the default layout type
        $this->layout = $this->get_layout();
        //
        //Output is the layout; this too cannot fail because of the default mode
        //type
        $this->mode = $this->get_mode();
        //
        //Set this page's driver data; all pages must implement this abstract
        //function. This is 
        $this->driver = $this->get_driver();
    }
    
    //Implemenet access to the protected variables. 
    function __getter($name){
        //
        //Check if the variable is already set
        if (__isset($this->$name)){
            //
            //It is set; return it
            $value = $this->$name;
        }
        else{
            //
            //Compile the get_ method and execute it. This step will throw an 
            //exception if the method is not defined
            $this->$name = $this->{"get_$name"}();
            //
            //Reurn the property
            $value = $this->$name;
        }
       //
      //Return teh value
        return $value;
    }
   
    //Display this page
    function display_page(){//page
        //
        //Use the query string to return the display style variables, viz., layout and 
        //display mode.
        $layout = $this->get_layout();
        $mode = $this->get_mode();
        //
        //Display the driver sql; th results will shar the same style as that of
        //this page
        $this->driver->display_data($this, $layout, $mode); 
    }

    
    //
    //By default, the driver of a page is null. You get a runtime error if you
    //try to reference the null driver
    function get_driver(){
        return null;
    }
    /*
    //
    //By default, the driver of a page is an sql
    function get_driver(){
        //
        //Get the sql statement from the query string array variables. It must 
        //be set
        if (!isset($this->arr['sql'])){
            //
            throw new Exception("No sql statement was found to drive page $this->name"); 
        }
        //
        $stmt = $this->arr['sql'];
        //
        //Get the curremt database
        $dbase = $this->get_dbase();
        //
        //Make a new sql object and retirn it as the driver; teh fields will be 
        //derived from the statement
        return new sql($dbase, $stmt);
        
    }
*/
    
    //
    //Display the content of a record on this page; that depends on the page. This
    //function was introduced so that we could override how a record is displayed.
    //By default, we display the fields
    function display_record(record $record, $layout=null, $mode=null){
        //
        //Display al the record's fields
       foreach($record->fields as $field ){
            //
            //Display the field; its parent source is this record being displayed
            $field->display_data($this, $layout, $mode, $record);
        }
    }
    
    //
    //By default, forreign key columns are shown in all pages. However, in a 
    //decendant, it is hident if its name matches the parent table
    function hide_foreign_keyfield(column_foreign $field){
        //
        return false;
    }
    
    //retuens the layout of this page
    function get_layout(){
        //
        //By this time, the layout type must have been set
        switch($this->layout_type){
            //
            case layout::label:
                $layout = new label();
                break;
            //
            case layout::tabular:
                $layout = new tabular();
                break;
            default:
                throw new Exception("Layout '$this->layout_type' is not known"); 
        }
        //
        return $layout;
    }
  
    //Returns the display mode of this page
    function get_mode(){
        //
        //By this time the display mode must have been set
        switch($this->mode_type){
            //
            case mode::input:
                $mode= new mode_input();
                break;
            //
            case mode::output:
                $mode= new mode_output();
                break;
            default:
                throw new Exception("Display mode '$this->mode_type' is not known"); 
        }
        //
        //Return the mode style
        return $mode;
    }
    
    
    //By default the checkbox is hidden; by default no checkbox is shown on any 
    //page. For certain operations that erquire more than one selection, e.g., merging, 
    //the checkbox is needed
     function hide_check_box(){
         //
         echo " hidden='true'";
     }
        
   
}

//A table-based page; it is abstract because it does not implement the driver
//for displaying pages
abstract class page_table extends page{
    //
    //The table name on which this page is based
    public $tname;
    //
    //The sql_edit that is shared between all deruivatives of page table. This
    //is different from a page_table's driver -- which is an sql based on sql_edit
    public $sql_edit;
    //
     //
    //Set the field name of the feld where the serialized sql edit is saved
    //the for this page of records. By default it is "serial_edit", but for 
    //a selector query it is "serial_selector".
    public $serial_driver_fname = "sql_edit";
    //
    //By default, teh primary key field is never displayed; page_selector 
    //overrides this property
    public $hide_primary_keyfield=true;
    //
    //A page_table extends a normal page one by being being specific 
    //to a database table. 
    function __construct(querystring $qstring, $tname=null){
        //
        //The index of this page, i.e, the field needed for populating the 
        //id attrubute of the resulting dom recrods is:-
        $this->index = field::id;
        //
        //Set the tname of this object befor the constructor because the construction
        //of this pag'es driver will NEED IT
        $qstring->bind_arg('tname', $tname, $this);
        //
        //Initialize the page system .
        parent::__construct($qstring);
        //
        //Initialize the sql_edit property for access in javascript. The driver 
        //is not as important as this
        $this->sql_edit = $this->get_sql_edit();
        
    }
   
    //Return the sql_edit query of the reference name (in this database) 
    //derived from unserializing a saved version -- rather than constructing one
    //from first principles. It is hoped that this will improve the response. 
    function get_sql_edit(){
        //
        //Let dbname and tname be database and table names of this query
        $dbname = $this->dbname;
        $tname = $this->tname;
        //
        //Get the name of the field where the serialized sql edit is saved
        //the for this page of records. By default it is "serial_edit", but for 
        //a selector query it is "serial_selector".
        $fname = $this->serial_driver_fname;
        //
        //Formulate the statement for retrieving the serial version of sql_edit
        $sql= "select $fname, serialization.`error` 
               from 
                (serialization inner join entity on serialization.entity=entity.entity
                ) inner join dbase on entity.dbase=dbase.dbase
                where dbase.name='$dbname' and entity.name='$tname'";
        //
        //Open a mutall_data database using the login credentials of an 
        //administrator. This means that anyone has unfettered access
        //to all the databases. Access to a database contents needs to be controlled
        //after it is shown.
        $mutall_data = new dbase(page_login::username, page_login::password, page_login::mutall_data);
        //
        //Use the mutall_data connection to execute the statement for 
        //retrieving the serial sql_edit
        if (!($stmt = $mutall_data->conn->prepare($sql))){
            //
            throw new Exception($mutall_data->conn->error);
        }
        //
        //Execute the statement
        $stmt->execute();
        //
        //Define a variable for binding the following variables:-
        //
        //...the serial version of the edit sql
        $serial_sql_edit = null;
        //
        //..any error associated with the serialization of sql_edit. How is 
        //usedthis
        $error=null;
        //
        //Bind the first column of the result to the serial_sql_edit variable
        if (!($stmt->bind_result($serial_sql_edit, $error))){
            //
            throw new Exception($stmt->error);
        }
        //
        //Check if there was any error raised during serialization
        if (!is_null($error)){
            //
            throw new Exception("Unable to get sql_edit driver due to this serialization error: $error");
        }
        //
        //Fetch the result and bind the values accordingly.
        if (!($result = $stmt->fetch())){
            //
            throw new Exception("No serial sql_edit found for table $tname in database $dbname.");
        }
        //
        //Unserialize the sql_edit
        $sql_edit = unserialize($serial_sql_edit);
        //
        //Return the unserialized version
        return $sql_edit;
    }
}

//Driver is the data that drives the display of a page. Record and Sql are 
//currently the only 2 known extensions of a driver
 abstract class driver extends mutall{
    //
    //The fields of a page driver can range from basic ones to complex cases
    //e.g., the column_primary and column_forein are cases comprises of multiple 
    //sub-fields.
    //
    //Fields used for deriving the selected columns of an sql. They are pubic 
    //because they need to be passed to the client applications (vis json strings)
    public $fields;
    //
    function __construct($fields=null) {
         //
         $this->fields = $fields;
         //
         //Initialize the parent mutall class
         parent::__construct();
     }
     //
     //Display data (as opposed to display page). Every mutall data must be 
     //displayable in teh style of the given page. In effect
     //display is the process that puts all basic mutall objects together. The 
     //parent is required for a fuller display. E.g., to display a hyperlink
     //we require the value (the actual href) and the more friendly name which
     //can only be obtained from the parent record
    abstract function display_data(page $page, layout $layout, mode $mode, driver $parent=null);
     
    //Write the data represented by this page driver to the database. 
    //(write_data() is easier to search than just write())
    abstract function write_data();
     
     //Saving this driver's data to the database does 2 things:
    //1- it writes the data to the database
    //2- it uploads files associated with this page to the server
    function save_data(){
        //
        //Write the data to the given database; that depends on the data object
        //(write_data is easier to search than just write)
        $this->write_data();
        //
        //Upload (picture) files to the server
        $this->upload_files();
     }
     
     //Mark the hidden fields on the given page
    function mark_hidden_fields(page $page){
        //
        //Get the hiddenn property of the page's query string
        //
        //First check wethether suh a propertyu exists
        if (isset($page->arr['hidden_fields'])){
            //
            //Get the hidden fields string
            $str = $page->arr['hidden_fields'];
            //
            //Convert the string to a list
            $hidden_list= explode(",", $str);
            //
            //Loop through all the fields of this driver and mark as hidden 
            //every field name in the hidden list
            foreach($this->fields as $field){
                //
                $field->hidden = in_array($field->name, $hidden_list) ? true: false; 
            }    
        }
        
    }
        
     
     //Upload all the necessary (image) files; 
    function upload_files() {
        //This process is valid oly if there are files to upload
        if (count($_FILES) === 0) {
            return;
        }
        //
        //Set the image directory as a subdirectory of current
        $images = "images";
        //
        //Exit if the image folder does not exist on teh server
        if (!file_exists($images)) {
            die("Folder $images does not exist on the server");
        }
        //
        //Upload valid file brought to the server
        foreach ($_FILES as $file) {
            //Retrieve the basename of the file
            $basename = $file['name'];
            //
            //Uploading is not valid for empty file name
            if ($basename == "") {
                continue;
            }
            //
            //Compile the absolute path on the server subfolder where the image will
            //be saved. We assume the same drive as this page. The relative one will 
            //not do for data movement on the server. Note the direction of the 
            //slashes (assuming a Windows server) to desigate an OS path
            $fullname = "$images/$basename";
            //
            //If the file exists do not overwite it
            if (file_exists($fullname)) {
                continue;
            }
            //
            //Transfer the temp filename to the correct server path -- using absolute
            //paths. If for any reason the move is not successful alert the user
            if (!move_uploaded_file($file["tmp_name"], $fullname)) {
                //There was an issue: report it
                echo "Error in uploading to file '$fullname'";
            }
        }
        return true;
    }
}

//expression is the smallest data unit that is not displayable meaningfully; it 
//can be constructed wholy as an sql-compatible string expression
class expression extends mutall{
    //
    function __construct($sqlstr) {
        //
        $this->sqlstr = $sqlstr;
        //
        //Initialize the mutall object
        parent::__construct();
    }
    //
    //The expression used as a string returns the sql string representation
    function __toString(){
        return $this->sqlstr;
    }
}

//Concat is an extension of an expression. It's string value is MySql's concat
//function expression. This expression was introduced to support construction of
//identification sql where id columns needed to be concatenated. Concat accepts
//only basic field values (not compund ones)
class expression_concat extends expression {

    //
    function __construct($basicfields) {
        //
        $this->basicfields = $basicfields;
        //
        //The sql string representation of a concat expression, e.g., concat(x1, x2, ...,xi)
        //xi is the i'th expression to be concatenated
        //
        //Extract the expression values from the basic fields 
        $values = array_map(function($field) {
            //
            return $field->fvalue;
            
        }, $this->basicfields);
        //
        //The value of concat is the function concat(c1,'/', c2, '/', ...) where ci are
        //columns,
        $value = implode(",'/',", $values);
        //
        $sqlstr = "concat($value)";
        //
        //Call teh inherited expression
        parent::__construct($sqlstr);
    }
}

//A binary expression is characterised by an operator. This sypports arithmetic,
//boolean and comparsion type of expressions
class expression_binary extends expression {

    function __construct($xp1, $operator, $xp2) {
        $this->xp1 = $xp1;
        $this->operator = $operator;
        $this->xp2 = $xp2;
        //
        $sqlstr = $this->xp1 . " " . $this->operator . " " . $this->xp2;
        //
        parent::__construct($sqlstr);
    }
}

//The null expression (is used as the default expresion in a field)
class expression_null extends expression{
    function __construct() {
        parent::__construct("NULL");
    }
}

//Numeric expressions. In contrast to text, numbers are not uotd
class expression_numeric extends expression {

    function __construct($number) {
        $this->number = $number;
        //
        //If a number is set, return it; otherwise retur a null
        $sqlstr = (isset($this->number)) ? $number : "NULL";
        //
        parent::__construct($sqlstr);
    }

}

//Text expressions. The key characteristics is the opening/closinng quotes
class expression_text extends expression {
    //
    //The actual text
    public $text;
    
    function __construct($text) {
        $this->text = $text;
        //
        //By default texts are single quote delineated; otherwise with are
        //double quoted.If text as both single and double quoted then we
        //say that is its malformed
        $sqlstr = "'" . $this->text . "'";
        //
        parent::__construct($sqlstr);
    }
    
}

//This is an expression derived from the table and field names of some sql.
class expression_column extends expression {
    //
    public $tname;
    public $fname;
    //
    //Construct a table column expression. Note that the table name can be 
    //missing. Hence the unnatural arrangement which puts fname before tname
    function __construct($fname, $tname=null) {
        //
        $this->tname = $tname;
        $this->fname = $fname;
        
        //The field name must be a string
        if (!is_string($fname)) {
            die("The field name, fname, must be a string in a column expression. It is a ".get_class($fname));
        }
        //
        //If the table name is not given, then ignore it in formulating the 
        //expression string
        if (is_null($tname)){
            //
            //Compile the string value of a column expression 
            //complete without a table name
            $sqlstr = "`$fname`";
        }
        //
        //Otherwise the table name must also be a string
        else{
            //
            //Both Fname and Tname must be strings
            if (!is_string($tname)) {
                die("The table name, tname,  must be a string in a column expression. It is a ".get_class($tname));
            }
            //
            //Compile the string value of a column expression complete with backticks
            $sqlstr = "`$tname`.`$fname`";
        }
        //
        parent::__construct($sqlstr);
    }
}

//condition is a named equality expression that is used for expressing join 
//conditions. Equality is a binary expression using the equals operator
class condition extends expression {

    public $id;
    //
    //The expressions for which equality is sought
    private $x1;
    private $x2;

    //A condition has 3 basic components: a id for indexing the condition in a 
    //multi-condition join; 2 expressions for which equality is sought
    function __construct($id, $x1, $x2) {
        $this->id = $id;
        $this->x1 = $x1;
        $this->x2 = $x2;
        //
        //The sql string version of a (equality) condition expression required for
        //implementing a join
        $sqlstr = $this->x1 . "=" . $this->x2;
        //
        parent::__construct($sqlstr);
    }
}


//A field is key component of a record. On its own, it cannot drive a page. Is
//this limitation warranted? Perhaps it is waranted. We do not envisage display
//of unrelated data on the same page under the buis project.
class field extends mutall{
    //
    //Constants that define special field names in an sql statement. The leading
    //_ is added to prevent possibilities of mixing up these special names with
    //user defined field names.
    //
    //The name of the primary key field; this field is useful for data updates
    const primary = "_primary";
    //
    //The id field of an sql column is used for hreferencing to a data row
    const id = "_id";
    //
    //The output field is the visible and friendly representation of the 
    //primary key data
    const output = "_output";
    //
    //The indexes associated with subfields of a relation field, i.e, primary
    //or foreign key field. These are important for accesing specific subfields 
    const map = array(
        "primary"=>field::primary,
        "id"=>field::id,
        "output"=>field::output
    );
    
    //The name of this field
    public $name;
    //
    //The field value (expression) is important for formulating sql statements
    //for selecting data. This makes sense only for the basic fields. Compound
    //fields, e.g., do not have a fvalue; their subfields do. 
    public $fvalue;
    
    //Marksa field as eiher hidden or not. Hidden fields are not displayed
    public $hidden;
    //
    //A basic field is characterised by a value of type expression and an 
    //optional name
    function __construct($name, expression $fvalue=null) {
        //
        $this->name = $name;
        //
        //If there is no value, use teh null expression
        $this->fvalue = is_null($fvalue) ? new expression_null: $fvalue;
        //
        //Initialize the inherited mutall object
        parent::__construct();
    }
    
    //The input html tag of a normal field is a text input box for a normal fiels
    function display_input_tag(page $page, mode $mode, record $parent){//field
        //
        $fname = $this->name;
        //
        echo "<input";
        //
        //The default input type is text; it is password if the field
        //name suggests so.
        if ($fname==="password"){
            echo " type='password'";
        }
        //
        //By default the type is plain text
        else{
            echo " type='text'";
        }    
        //
        //To match this input element to the "for" property of a label, you
        //need to include the id
        echo " id='$fname'"; 
        //
        //Retrieve the (input) value of this field from the parent record's 
        //property that matches the field name
        $value = isset($parent->values->$fname) ? $parent->values->$fname: "";
        //
        //Display the input value
        echo " value='$value'";
        //
        //Echo the hidden status
        echo $mode->hidden(mode::input);
        //
        //Close the input
        echo " />";
    }
    
    //The output html element of a normal field is some text content
    function display_output_tag(page $page, mode $mode, record $parent){
        //
        //Open our own user-defined output tag
        echo "<output";
        //
        //Hide this data if the output mode is input
        echo $mode->hidden(mode::output);
        //
        //Close the data arguments
        echo ">";
        //
        //Output the value of this field as the text content -- if it exists
        //from the parent record's values
        echo isset($parent->values->{$this->name}) ? $parent->values->{$this->name}: "";
        //
        //Close data tag
        echo "</output>";
    }
    
    //Tests if this field is part of the default id index of the reference table 
    //in which it is taking part
    function field_is_id($dbase) {
        //
        //Get this field's value
        $fvalue = $this->fvalue;
        //
        //Identification values are always column exressions
        if (get_class($fvalue) === "expression_column") {
            //Get the expression;s table name
            $tname = $fvalue->tname;
            //
            //Get the expression's field name
            $fname = $fvalue->fname;
            //
            //Get teh named table from the global database
            $table = $dbase->get_table($tname);
            //
            //Get the default index of this columns source table
            $cols = $table->first_index_cols();
            //
            //See if this column exists
            return array_key_exists($fname, $cols);
        }
        //Otherwise this cannot be an indetification field
        else {
            return false;
        }
    }
    
    //By default an ordinary field cannot be modified using database insert and
    //update methods; the only known modifiable fields are column and 
    //column_foreign. The rationale here is that field is a derived column
    function is_modifiable(){
        return false;
    }
    
     //A field is hidden if its hidden attribute is set. The hiding is done
     //during the creation of a field
    function is_hidden(page $page){//field
        //
        return isset($this->hidden) && $this->hidden ? true:false;
    }
    
     
    //Tries to return the writable value of a field from the given list. If the
    //list is missing, we try to get it from this mutall object.
    //An ordinary field cannot be written to a database and it is illegal to try 
    //to do so; in contrast, a table column can. 
    function try_writable_value($values=null, &$value){
      throw new Exception("Field named $this->name of type get_class($this) cannot be modified");  
    }
    
    //A basic field is splits into itself
    function split() {
        yield $this;
    }

    //Returns the alias of a field depending on whether the name is given or not
    function alias() {
        //
        if ($this->name) {
            //
            return " AS `" . $this->name . "`";
        }
        //Otheerwise the default is no alias
        return "";
    }

    //The expression value of a basic string...
    function __toString() {
        return (string) $this->fvalue;
    }
    
    //Display the data for this field. The parent of a field is the record from 
    //which it was derived.
    function display_data(page $page, layout $layout, mode $mode, driver $record=null){//field
        //
        //Get the field name
        $fname = $this->name;
        //
        //Open the dom field tag to start outputting the attributes.
        echo "<$layout->field_tag_name";    
        //
        //Add the click event that should mark this dom field. This is a mutall
        //function that is reached from a javascript expression 'page->jsxp'.
        //By default the expression has the same value as some variable named
        //the same as the page and created when the page is loaded.
        echo " onclick='$page->jsxp.select_dom_field(this)'";
        //
        //Add a name to this field tag; it is needed for referencing it
        echo " fname='$fname'";
        //
        //Hide/show this field -- depending on the page
        echo $this->is_hidden($page) ? " hidden='true'": "";
        //
        //Close the dom field properties
        echo ">";
        //
        //The label class is valid only for the labels layout
        //
        $layout->show_label_name($fname);
        //
        //Display the metadata, input and output data for this field; the metadata 
        //are hidden; so is either the input or output data -- depending on the
        //edit mode
        $this->display_value($page, $mode, $record);
        //
        //Close the fied tag name
        echo "</$layout->field_tag_name>";
    }
    
    //The display value of any field value comprises of 3 tags: a mandatory 
    //output,an optional input and other supporting metadata. The metadata are 
    //always hidden. The input (or output) data is hidden or not, depending on 
    //the type of display mode. The output of a primary key is always visible.
    //The parent record carries the value to be displayed
    function display_value(page $page, mode $mode, record $parent){
        //
        //Display the output html tag
        $this->display_output_tag($page, $mode, $parent);
        //
        //Display the input html tag. The page is needed, e.g., for proper 
        //referencing the onclick input field of a foreign key relation
        $this->display_input_tag($page, $mode, $parent);
        //        
        //Display the metadata tags; the page is not needed for the dislay
        $this->display_metadata_tags($mode, $parent);
    }
    
    //An ordinary field has no metadata; so this does noting
    function display_metadata_tags(mode $mode, record $parent){}
    
}


//The column class is an extension of a field, the key property being that it
//is associated with a specific table of a dataabse. As such, its data can be
//saved to a database. In contrast, a field may be an expression; therefore it 
//cannot be edited.
class column extends field{
    //        
    //Comes from column name; its the name of the field
    public $name;
    //
    //The general type of data: string, integer, double etc
    public $type;
    //
    //Useful for testing if a column can hold a null or not. This is useful for
    //for flagging empty sells as errors or not
    public $is_nullable;
    //
    //Comes from character_maximum_length. This is meaninful oly for string 
    //data types
    public $length;
    //
    //The type of a column is useful for differentiating between integers. 
    //E.g., A boolean data type (which is an integer) has column type int(1); 
    //a long integer has a different column type: int(11)
    public $column_type;
    //
    //Comes from column_key and is used for identifying primary keys (PRI),
    public $key;
    //
    //Comes from column_comment and used for passing further user defined data 
    //to a column during data modelling
    public $comment;
    //
    //The table name from which the column comes from is useful for 
    //formulating complete columen names, e.g., client.visit
    public $tname;
    //
    //Fields needed for determining whether this is a foreign key field or not
    public $fk_table_name;
    public $fk_col_name;
    
    //Initialize a column using the given description. The description can be
    //eiter an array (as returned by column_fields() or a stdClas object as
    //assumed in column mutall::activate. Only publicly defined properties
    //will be used. The name and source table of the column must be known.
    function __construct($tname, $name, $description){ 
        //
        //The name of the column must be set 
        //
        //The value of a column field is a column expresson of the table 
        //and column name
        $value = new expression_column($name, $tname);
        //
        //Initialize the basic field. The local name of the field is the 
        //same as theh column name. Its value is the same as column name and
        //the base sql is that of the column
        parent::__construct($name, $value);
        //
        //Offload the descriptions to this column
        $this->offload_properties($description);
    }

    //Initialization of sql_edit using an ordinary column simply adds itself 
    //to those of te sql using the (field) name index. The joins are not affected.
    //In contrast, adding a foreign key column to teh sql affects its joins.
    function initialize_edit_sql($sql_edit) {//column
        //
        $sql_edit->fields[$this->name] = $this;
    }

    //
    //An ordinary column initilaizes the criteria sql by adding itself to the 
    //sql's fields, indexed by their names. The joins are not affected. What is
    //the criteria sql, and what is it used for? See description of sql_hint. 
    //Shouldn't it be renamed to sql_output?
    function initialize_hint_sql($sql_hint) {
        //
        //Formulate the full column name for the criteria sql.
        $name = $this->tname . "_" . $this->name;
        //
        //Add this column to the fields of the sql
        $sql_hint->fields[$name] = $this;
    }
    
    //An ordinary table column field is modifiable. In contrast the primary 
    //key column is not. So, the primary key column overrides this fact.
    function is_modifiable(){ return true; }
    
    //To convert columns into unique string names for use as aliases in
    //an sql statement, formulate them as follows:-
    //$tname.$fname
    function __toString() {
        return $this->tname . "_" . $this->name;
    }
    
    //
    //By default the html input type of a normal column is text. In contrast
    //that of a foreign key column is a button. In future we will need the 
    //textarea type in order to edit long text.
    function type() {
        return "text";
    }

    //Tries to return the writable value (through teh given reference variable
    //of a field from the given list. If the values list is missing, we try to 
    //get it from this column's values and if successful, the function returns 
    //true with the bounded value; otherwise it returns false. Quotation marks
    //are added to the value regardless (of the data type). Mysql requires them
    //for insert and update operations and will convert the data to the corret 
    //type -- depending on the columns data type. 
    function try_writable_value($values=null, &$value){
        //
        //Set the source values to this field's values property, if they are 
        //not prrovided
        if (is_null($values)){$values = $this->values;}
        //
        //Get this field's name
        $fname = $this->name;
        //
        //See if a property of source named after the column exists and it has 
        //something then return the quote delimited value
        if (isset($values->$fname) && (!is_null($values->$fname)) && $values->$fname!==''){
            //
            $value = "'{$values->$fname}'";
            //
            return true;
        } 
        //   
        return false;
    }
    
}

//Columns used for establishing relationsions. This class is extended by primat
//and foreign key fields
class column_relation extends column{
    //
    //Relation columns can be expressed in terms of subfields. The subfields 
    //are initialized when we initialize the sql data
    public $subfields;
    
    function __construct($tname, $name, $description) {
        parent::__construct($tname, $name, $description);
    }
    
    //When a relation field is split, it yields one of its subfields
    function split() {
        //
        foreach ($this->subfields as $subfield) {
            //
            yield $subfield;
        }
    }
    
    //Display the output tag of a relation column based on the output subfield
    function display_output_tag(page $page, mode $mode, record $parent){
        //
        //Open our user-defined output tag
        echo "<output ";
        //
        //Echo the hidden status
        echo $mode->hidden(mode::output);
        //
        //Close the output attributes
        echo ">";
        //
        //Get the field name of the output subfield of this relation column
        $fname = $this->subfields->output->name;
        //
        //Output the value of this field as the text content -- if it exists --
        //--irrespective of the display mode
        echo isset($parent->values->{$fname}) ? $parent->values->{$fname}: "";
        //
        //Close data tag
        echo "</output>";
    }
    
    //The input element of a relation field is an input button, which when 
    //you click on it, evokes field editing. (For the primary key, this is always
    //hidden)
    function display_input_tag(page $page, mode $mode, record $parent){//column_relation
        //
        //Echo the input tag
        echo "<input";
        //
        //The inout is of the button type
        echo " type=button";
        //
        //On clicking, call the edit function associated with the javascript
        //expression of the given page
        echo " onclick='$page->jsxp.edit_field(this)'";
        //
        //The value showing on the input button coems from the criteria subfield
        //output 
        //
        //Get the output subfield's name
        $fname = $this->subfields->output->name;
        //
        //Get the basic field value from the given record, if available; 
        //otherwise return an empty value
        $value = isset($parent->values->$fname) ? $parent->values->$fname : "";
        echo " value=\"".$value."\"";
        //
        //The input will be hidden if we are in output mode
        echo $mode->hidden(mode::input);
        //
        //Close the input
        echo "/>";
    }

    
    //Display the metadata tags of a relation field-- based on subfields
    //that excludes the output index. Metadata are hidden, regardless
    function display_metadata_tags(mode $mode, record $parent){
        //
        //Display the html tags associated with the subfields a forein key feld
        foreach($this->subfields as $index=>$subfield){
            //
            //Handle all subfields as hidden metadata except the output
            if ($index!=="output"){
                //
                //Get the field name of the subfield
                $fname = $subfield->name;
                //
                //Retrieve the tag's data from the parent record and display it
                //as the text content of the element named index
                //Take care of new (empty) records
                echo "<$index hidden=true>";
                echo isset($parent->values->$fname) ? $parent->values->$fname: "";
                echo "</$index>";
            }
        }
    }
 }

//A primary key column inherits from a normal column and uses the Primary key trait 
class column_primary extends column_relation{
    
    //
    //Extend the normal column
    function __construct($tname, $name, $description) {
        parent::__construct($tname, $name, $description);
    }

    //Display the output tag of a primary key field. This tag is never hidden 
    //irrespective of the given mode; so we need to override the incoming mode
    //to always output
    function display_output_tag(page $page, mode $modeIn, record $parent){
        //
        //Define the output mode
        $mode = new mode_output();
        //
        //Now display the output tag of the inherited column_relation in the
        //new overriding mode
        parent::display_output_tag($page, $mode, $parent);
    }
    
    //The primary key has no input tag --as it cannot be edited
    function display_input_tag(page $page, mode $modeIn, record $parent){//column_primary
    }
    
    //
    //Initialization of sqlEdit using this primary key columnn produces a 
    //composite field derived from sql_selector query. The field has 3 subfields: 
    //primary, output and id.
    // 
    //The primary key column does not contribute any joins to given sql edit.
    //The relational distance is relevant for only the foreign key fields
    function initialize_edit_sql($sql_edit) {//column_primary
        //
        //Formulate an sql, called a sql_selector (because it is generally
        //used for selecting records in a foreign key field input) based on the 
        //same reference table name and database as those of sqlEdit
        $sql_selector = new sql_selector($sql_edit->dbase, $sql_edit->tname);
        //
        //Save the subfields of this primary key field as properties (rather 
        //than indexed array). This is to ensure compatibiity with the js
        //environment
        $this->subfields = new stdClass();
        //
        //Step through a map the indexes the 3 subfields of a relation field, 
        //i.e, primary or foreign key field.
        foreach(field::map as $index=>$fname){
            //
            //Set the named field of sql_selector to be the subfield 
            //under the given index. Note that the fields of the sql 
            //selector is an indexed array while those of the subfields
            //is a object. (This needs to be consistent; the problem is that
            //js does not have indexed arrays!! They are converted to objects
            //thus making sharing of data between php and js a bit trouble some)
            $this->subfields->$index = $sql_selector->fields[$fname]; 
        }
        //
        //Add this column to those of sql edit
        $sql_edit->fields[$this->name] = $this;
        //
        //Transfer the joins of sql_selector to those of sql edit. Its a simple merge.
        //Will the merge respect the array indexing? Check.
        $sql_edit->joins = array_merge($sql_edit->joins, $sql_selector->joins);
    }

    //
    //It is illegal to use the primary key field for identification 
    //or decription purposes. 
    function initialize_hint_sql($sqlHint) {
        throw new Exception("Primary key " . $this->name . " in table " . $sqlHint->name . " should not be used for identification");
    }

    //A primary key is not modifible
    function is_modifiable(){ return false; }
    
    
    //Hide the primary key field if it does not add value to a display. In 
    //descendant pages, it does not add value at all. In other pages, it adds 
    //value ony if there its output is mde of more than one column
    function is_hidden(page $page){//column_primary
        //
        return $page->hide_primary_keyfield;
    }
   
}

//A foreign key column inherits from a normal column and uses the Foreign trait
class column_foreign extends column_relation {
    //
    function __construct($tname, $name, $description, $foreign) {
        //
        //Initialize the parent column relation
        parent::__construct($tname, $name, $description);
        //
        //??????????
        $this->foreign = $foreign;
    }

    //
    //Initialization of sql_edit using this foreign key columnn produces a 
    //composite field derived from the sql_selector query and the foreign key 
    //table. The join is a simple left join added to the given sql_edit
    function initialize_edit_sql($sql_edit) {//column_foreign
        //
        //Formulate sql selector query based on the foreign key table name
        $sql_selector = new sql_selector( $sql_edit->dbase,$this->foreign->table_name);
        //
        //Save the subfields of this foreign key column as properties (rather 
        //than as an indexed array) from the sql_selector
        $this->subfields = new stdClass();
        //
        //Step through the mapping of index and special sql fields
        foreach(field::map as $index=>$fname){
            //
            //Customize this field so that it is correctly referenced in sqlEdit
            //
            //The custom field value is a column expression whose table is 
            //sql_selector and field name is that of the field
            $cfvalue = new expression_column($fname, $sql_selector->name);
            //
            //The customized field name is that of the field name prefixed by 
            //the name of the sql_selector table name
            $cfname = $sql_selector->name . $fname;
            //
            //Formulate the custom field as the subfield
            $cfield = new field($cfname, $cfvalue);
            //
            //Set the subfield to this subfields, using the correct index
            $this->subfields->$index = $cfield; 
        }
        //
        //Add this field to those of the edit sql using the field name index
        $sql_edit->fields[$this->name] = $this;
        //
        //Add the left join
        //
        //The primary key expression is derived from the "primary key field name" 
        //and the name of the sql_selector query 
        $primaryxp = new expression_column(field::primary, $sql_selector->name);
        //
        //The foreign key field is the value of the column expression
        $foreignxp = $this->fvalue;
        //
        //The join condition id is formulated from the foreign key field name
        $cid = $this->foreign->column_name;
        //
        //Formulate the join condition expression
        $conditionxp = new condition($cid, $primaryxp, $foreignxp);
        //
        //Formulate the left join. Left joins to a reference table have a 
        //relational distance of 0.
        $join = new join("LEFT", $sql_selector, $conditionxp, 0);
        //
        //Add it to the edit sql's joins indexed by the name of the sql_selector table
        $sql_edit->update_joins($sql_selector->name, $join);
    }

    //
    //Use this foreign key column to initialize the given (primary) criteria sql 
    //by expanding both its joins and fields using a another (secondary) criteria 
    //sql derived from the forein key table name.
    //Remember to icretaes the relationa; distance by one, when you create a 
    //new sql_hint
    function initialize_hint_sql($sqlHintPrimary) {
        //
        //Get the foreign key table name
        $fktname = $this->foreign->table_name;
        //
        //Get the relational distance of the primary criteria sql
        $rdistance = $sqlHintPrimary->rdistance;
        //
        //Formulate the secondary criteria sql using the foreign key table name as
        //the reference. Increase the relational distance by 1
        $sqlHintSecondary = new sql_hint($sqlHintPrimary->dbase, $fktname, $rdistance+1);
        //
        //Update primary criteria sql fields using the secondary ones
        foreach ($sqlHintSecondary->fields as $index => $field) {
            //
            $fvalue = $field->fvalue;
            //
            $name = $field->name;
            //        
            $sqlHintPrimary->fields[$index] = new field($name, $fvalue);
        }
        //
        //FORMULATE THE INNER JOIN CORRESPONDING TO THIS FOREIGN KEY COLUMN
        //
        //Get tHe foreign key table expression
        $fktablexp = $sqlHintSecondary->dbase->get_table($fktname);
        //
        //The primary expression is derived from the primary field value of sql_selector
        //and the primary key field name
        $primaryxp = new expression_column($this->foreign->column_name, $fktname);
        //
        //The foreign key field is the value of the column expression
        $foreignxp = $this->fvalue;
        //
        //Formulate the indexing name of the condition. This should be the
        //same as the name of this foreign key field
        $cname = $this->name;
        //
        //Formulate the join condition expression
        $conditionxp = new condition($cname, $primaryxp, $foreignxp);
        //        
        //Use this foreign key column and the current relational distance to 
        //formulate an inner join expression for the criteria sql. 
        $myjoin = new join("INNER", $fktablexp, $conditionxp, $rdistance);
        //
        //Use the join to update primary criteria sql; the order matters
        $myjoin->update_sql($sqlHintPrimary);
        //
        //Get the secondary criteria joins
        $joins = $sqlHintSecondary->joins;
        //
        //Merge the primary and secondary joins to give the primary ones
        array_walk($joins, function($primaryjoin) use($sqlHintPrimary) {
            $primaryjoin->update_sql($sqlHintPrimary);
        });
    }

    //Foreign key details: a stdClass comprising of table_name and column_name
    public $foreign;

     //A foreign key field is odifiable. (In contrast the prmary key is not)
    function is_modifiable(){ return true; }
   
    //
    //The foreign key field is associated with the html input element is of type 
    //button -- thus overriding the default "text" type
    function type() {
        return "button";
    }

    //
    //Returns the foreign key table of this field. This is a method -- rather
    //than property, to avoid havig to sort th tables by order of dependency
    function get_foreign_table() {
        //
        //Return the table indexed by the foreign key table bame
        return $this->table->dbase->get_table($this->foreign->table_name);
    }
    
    //Hide this foreign key field from displaying on the given page
    function is_hidden(page $page){//column_foreign
        //
        //Override teh parent hiding if necessary
        return $page->hide_foreign_keyfield($this) ? true : parent::is_hidden($page);
    }
    
    //Tries to return the writable value of a foreign key field from the given 
    //list. If the list is missing, the fucntion to gets it from this mutall 
    //object. If sucessful, the function returns a quoted value; otherwise it 
    //returns false. Quotation marks are needed by Mysq for insert and update 
    //operations, regardless of the data type.  Mysql will convert it to the 
    //corret data type -- depending on the field type. 
    function try_writable_value($values=null, &$value){
        //
        //Set the values to this field if it is missing
        if (is_null($values)){$values = $this;}
        //
        //Get the name of the primary key subfield
        $fname = $this->subfields->primary->name;
        //
        //See if a property of source named the same as fname exists. If it does
        //return the quote delimited value; otherwise return the null keyword
        if (isset($values->$fname) && (!is_null($values->$fname))&& $values->$fname!==''){
            //
            $value = "'{$values->$fname}'";
            //
            return true;
        } 
        //   
        return false;
    }
    
    
    //Display the metadata tags of a foreign key field; these are the metadata
    //hat are general to the parent column_relation and those that are specifc 
    //to a foreign key field
    function display_metadata_tags(mode $mode, record $parent){
        //
        //Display the parent column_relation metadata
        parent::display_metadata_tags($mode, $parent);
        //
        //Display the foreign key table as it is needed for editing
        //foreign keys
        echo "<fk_table_name hidden=true>";
        echo $this->foreign->table_name;
        echo "</fk_table_name>";    
    }
    
    //
    //Returns an sql expresssion that is a more friendly representation of a
    //foreign key column. The general shape of the expression is 
    //concat(`id1`,'/', `id2`...) 
    //where idi is the i'th identification field of the foreign key table of this
    //field.
    function get_fk_exp() {
        //Get the foreign key table of this column
        $fktable = $this->get_foreign_table();
        //
        //Debug
        //echo "<pre>".print_r($this, true)."</pre>"; die("");
        //
            //Get the identification fields of the first index of the foreign 
        //table
        //
            //Get the columns of the default index of the foreign table
        $cols = $fktable->first_index_cols();
        //
        //Map them to their string expressions
        $exps = array_map(function($col) {
            return (string) $col;
        }, $cols);
        //
        //Concatenate the expressions with a ,'/', separator so that the 
        //values come out slash separated
        $str = implode(", '/',", $exps);
        //
        return "concat($str) AS " . $this->name . "_" . $this->name . "_ext";
    }
    
}

//dbase a mutall object that represents a named mysql PageDatabases
class dbase extends mutall {
    //
    //Public variables of a database
    public $username;
    public $password;
    public $dbname;
    //
    //The database connection is protected so that it is not jsonable.
    protected $conn;
    //
    //The base tables of this database; what about the views?
    protected $tables = [];
    // 
    function __construct($username, $password, $dbname) {
        //
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
        //
        //Initilaize the mutall object
        parent::__construct();
        //
        //Establish the database connection
        $this->set_connection($username, $password, $dbname);
    }
    
    //Define the magic getter and setter functions for this dbase's protected properties
    function __get($prop){
      return $this->$prop;  
    }
    function __set($prop, $value){
      $this->$prop = $value;  
    }
    
    //Establish the database connection
    function set_connection($username, $password, $dbname) {
        //
        //Open the database using the given credentials on the local server
        $this->conn = new mysqli("localhost", $username, $password, $dbname);
        //
        //Check for connection errors
        //
        //Check for connection error
        if ($this->conn->connect_error){
            die("Error. ".$this->conn->connect_error);
        }
    }
    
    //When you unsrialize this database, re-establish the connction.
    function __wakeup() {
        $this->set_connection($this->username, $this->password, $this->dbname);
    }

    //Returns the requested table, first by looking up from protected tables, 
    //then from first principles
    function get_table($tname) {
        //
        //Return the table if it is set for this database
        if (isset($this->tables[$tname])) {
            return $this->tables[$tname];
        }
        //
        //Otherwise create a new standard table from first principles
        else {
            $table = new sql_table($this, $tname);
            //
            //Update this database's table list
            $this->tables[$tname] = $table;
            //
            return $table;
        }
    }

    //Opening a database assumes that the user has already logged in and 
    //that the database parameters are available from session variables
    static public function open() {
        //See if the PageDatabases session variable has been set or not
        if (!isset($_SESSION['PageDatabases'])) {
            die("Please login to accesss the desired database");
        }
        //
        //Get the database PageDatabases
        $PageDatabases = $_SESSION['PageDatabases'];
        //
        //Open and return the requested database
        return new dbase($PageDatabases);
    }
    //
    //Get the current period from the database
    function get_current_period() {
        //Get the opened connection
        $conn = $this->conn;
        //
        //Formulate teh sql
        $sql = "select year, month from period where is_current";
        //
        //Execute the sql
        if (!$result = $conn->query($sql)) {
            die($sql . "<br/>" . $conn->error);
        }
        //
        //Fetch the only oe value
        $resulttype = $result->fetch_assoc();
        //
        //There must be at least one current period, otherwise its an error
        if (!$resulttype) {
            die("There must be at least 1 curent period");
        }
        //
        //Return teh year and month
        return $resulttype['month'] . "/" . $resulttype['year'];
    }
    
}

//A compound field is a list that is a a mixture of basic and compound fields
//derived from some column

//
//This class models the Sql join. The general expression of a join is:-
//($hook $jointype JOIN $a on $a->$primaryfname = $h1 and $a->$primaryfname = $h2...)
//The $hook may be a simple database table sql or a sub-query or another join
//expression. The hi's are field values of the foreign key fields in the $hook
//expression
class join {

    //Type of the join -- left or inner
    public $join_type;
    //
    //The foreign key table of a join. The id if a join is derived from this 
    //table.
    public $fktable;
    //
    //The id of a join is formulated from the foreign key table that is
    //participating in the join
    public $id;
    //
    //The join conditions as a list of equality expressions
    public $conditions;
    
    //The relaional distance is used for sorting the joins before they are 
    //converted to strings.
    public $rdistance;

    //A join is a foreign key table expression accompanied by one or more join 
    //conditions expressed as equality expressions. More expressions may be 
    //added to the join to take care of multiple conditions. The join type may 
    //inner or left. The relaional distance is used for sortong the joins
    //before they are converted to strings.
    function __construct($join_type, $fktablexp, $condition, $rdistance){
        //
        $this->join_type = $join_type;
        $this->fktable = $fktablexp;
        $this->rdistace = $rdistance;
        //
        //The id of a join is formulated from the foreign key table that is
        //participating in the join
        $this->id = $fktablexp->tname;
        //
        //Start a join conditions list using the given expression
        $this->conditions[$condition->id] = $condition;
    }

    //Convert the join to a string. It has the form:-
    //$join_type JOIN $fktablexp on $c1 AND $c2 AND $c3...
    //where $ci is the i'th sql condition expression, e.g., client.zone=zone.zone
    function __toString() {
        //
        //Map the join condition expressions to thier sql string equivalents
        $condition_strs = array_map(function($condition) {
            return (string) $condition;
        }, $this->conditions);
        //
        //And-separate the string conditions
        $condition_str = implode(" AND ", $condition_strs);
        //
        return " $this->join_type JOIN " . $this->fktable->value() . " ON " . $condition_str;
    }

    //Update the joins of the given sql using this one. This proceeds by either 
    //expanding the condition of an existing join, adding a completely 
    //new join, or reporting cyclic errors. If a new join is added, it will be
    //indexed by its foreign key table name.
    function update_sql($sql) {
        //
        //Test if this join's id is already participaing in the given Sql joins
        if (array_key_exists($this->id, $sql->joins)) {
            //
            //It is; test for cyclic loop
            //
            //Get the identified sql join
            $sqljoin = $sql->joins[$this->id];
            //
            //See if any of the conditions of this join exists in the sql joins.
            foreach ($this->conditions as $condition) {
                //
                //Check if this condition exists in the sql join
                if (array_key_exists($condition->id, $sqljoin->conditions)) {
                    //
                    //It does (exist). This is an endless loop. Report it
                    throw new Exception("Cyclic condition. This foreign key " . $condition->id . " is aready in the join " . $sqljoin);
                }
                //
                //Otherwise, add the condition of this join to the sql join
                {
                    $sqljoin->conditions[$condition->id] = $condition;
                }
            }
        }
        //
        //Otherwise, i.e., this join is not partcipating in the current sql joins
        //Add it, using its id as the index
        else {
            $sql->update_joins($this->id, $this);
        }
    }

}

//The mode of displaying the data. Currently only one property is known:type. Why
//promote this to a class then? To allow the use of a display arguement that 
//whose class is known, e.g., display(layout $x, mode $y) 
//class. It is an abstract class, so that we canot create objects out of it. This
//means we have to creat wither input ot output modes explicitly
abstract class mode extends mutall{
    //
    //Types of mode of presentng data in any layout
    public $type;
    //
    //The data can be edited
    const input = "mode_input";
    //
    //The data is highly formated but cannot be edited
    const output = "mode_output";
    
    function __construct($type){
        //
        $this->type = $type;
        //
        //Initalize the parent mutall object
        parent::__construct();
    }
    
    //Returns the hidden attribute or nothing depending on the specfifed
    //mode type
    function hidden($mode_type){
        //
        //If the given mode type matches that of creating this mode tyoe, then 
        //it should be shown, i.e., there is no hidden attribute
        return ($this->type ===$mode_type) ? "": " hidden=true";
    }
}

//The input mode. The justification for mode extensions is so that we can
//activate these classes without requirring any arguments
class mode_input extends mode{
    //
    function __construct() {
        //
        parent::__construct(mode::input);
    }
}

//The output mode
class mode_output extends mode{
    //
    function __construct() {
        //
        parent::__construct(mode::output);
    }
}


//Layout allows us us to manage the different styles of arranging data 
//presentations on a page. It is an abstract class; that means that tthe user
//has to create the descenant classes explicity
abstract class layout extends mutall{
    //
    //Names of the layout types
    const tabular = "tabular";
    const label = "label";
    //
    //Type of layout
    public $type;
    //
    //The field and record tag names used by the layout to markup a html page
    public $field_tag_name;
    public $record_tag_name;
    public $envelop_tag_name;
    //
    //Every layout must tell us how the fields and records in a html document
    //are demarcated. 
    function __construct($type) {
        //
        $this->type = $type;
        //
        //Initialize the top level mutall object
        parent::__construct();
    }
    
    //By default, a layout has no field label. A labeled layout does
    function show_label_name($fname){
    }
    
    //By default, a layout has no header
    function display_layout_header(sql $sql, page $page) {}
        
}    

//This class groups operations revolving around the arrays $_GET and $_POST
//used for passing data to the server from a client. It is extendded by page
class querystring extends mutall{
    //
    //
    //Hold the query string array here.
    public $arr=[];
    //
    function __construct(array $arr){
        //
        $this->arr= $arr;
        //
        //Initialize the parent mutall system
        parent::__construct();
    }
    
    //
    //Create a querystrng from the requested global variable
    static function create($var){
        //
        //Filter the global variable $_GET or $POST to avoid the direct use of global 
        //variable warning
        $arr = filter_input_array($var);
        //
        //Set the array or an empty list if filetring faild
        $arr2 = is_null($arr) ? [] : $arr;
        //
        //Use the array to create the querystring
        return new querystring($arr2);
    }
 
    
    //Try to get the value of the named argument of some method to one of the 
    //following:-
    //- the value in the given variable (if valid);
    //- the corresponding indexed value in this querystring's array;
    //- the corresponding property in the given page
    //in that order.
    //The function returns true and binds the value argument variable to the 
    //found value; otherwise it returns false.
    //This method was designed so that a boolean value of false is not intepreted 
    //as a failure of this function. Note that this function does not set the
    //the named property on this object, unlike try_bind_arg version.
    //The page is also a reference variable because with will be re-set if  
    //necessary
    function try_get_arg($name, $variable, &$value, $validate=FILTER_DEFAULT, page &$page=null){
        //
        //If the page is not given, then assume that this object is a page (not
        //a querystring). This is the reason teh page argument is defiend as a 
        //reference variable.
        if (is_null($page)){
            //
            $page = $this;
        }
        //
        //Let $v be teh value we want;
        $v = null;
        //
        //If the given variable has a valid value, then its the value we want.
        if (!is_null($variable)){
            //
            $v = $variable; 
        } 
        //If this query string has an array value indexed by the named argument
        //then its teh value we want
        elseif (isset($this->arr[$name])){
            //
            $v = $this->arr[$name];
        }
        //
        //If the given page has a prperty named the same as the agument, then 
        //its the one we want
        elseif (isset($page->$name)){
            //
            $v = $page->$name;
            //
        }
        //
        //We have not found the named argument; return a false
        else{
            return false;
        }
        //
        //Turn the value we want into the correct type -- and bind it to the
        //incoming value
        $value = filter_var($v, $validate);
        //
        return true;
    }
    
    //Try to bind the given variable to a value resulting from a try_get_arg
    function try_bind_arg($name, &$variable, page &$page=null, $validate=FILTER_DEFAULT){//querystring
        //
        //Define the value we want
        $value = null;
        //
        //Try to get the value of the named argument.
        if ($this->try_get_arg($name, $variable, $value, $validate, $page)){
            //
            //Set the variable to the value we found, i.e, bounded earlier
            $variable = $value;
            //
            //Ensure that the property of page with theh same name as teh argument
            //is also set. Note that page is at this point definitely set to a
            //non-null; thatns to having passed it as a reference variable
            $page->$name = $variable;
            //
            return true;
        }
        return false;
    }
    
    //Bind the named argument of some method to some valid value. Throw an 
    //exception if this is ot possible
    function bind_arg($name, &$variable, page &$page=null, $validate=FILTER_DEFAULT){
        //
        if ($this->try_bind_arg($name, $variable, $page, $validate)){
            //
            //Return the variable on succesful binding
            return $variable;
        }
        //
        //In future, report both the __CLASS__ and __METHOD__ where this binding
        //was requested. Consider using debug_backtrace and trigger_error() 
        //functions
        throw new Exception("Unable to set argument '$name' for page '$page->classname'");
    }
    
}

//A mutall record can be used for driving page displays; it is associated with 
//values that relate to its fields
class record extends driver{
    //
    //The values of a record -- stdClass object whose properties have a 
    //relationship withe the record's fields (and subfields)
    public $values;
    //
    //Notes about a record:-
    //- The table name of a record is optional; if not provided, the record 
    //  cannot be saved to a database, e.g., the case of logging in. This was the
    //  justification for doing away with record an extension of a "writeable" 
    //- The  reference table parameter was added so that access to the indices (needed
    //  for saving a record) is consistent for both sql and record
    //- The statement is partially tracking the sql from which the record was generated. When
    //  is this useful
    function __construct($fields, $dbase=null, $tname=null, $reftable=null, $stmt=null, $values=null){
        //
        $this->dbase = $dbase;
        $this->tname=$tname;
        $this->reftable =$reftable;
        $this->stmt=$stmt;
        $this->values = $values;
        //
        //Initialize the inherited page driver
        parent::__construct($fields);
    }
    //
    //Write, i.e., insert or update, this record to the current database.
    function write_data(){
        //
        //A write is valid for this record only the table name is known AND AN 
        //IDENTIFICATION INDEX is available; else report an error. 
        if (!isset($this->tname) || !isset($this->reftable->indices)){
            //
            throw new Exception("No table (or identification index) is found for the database write operation");
        }
        //
        //Determine if we need to insert new or update existing data; we update
        //if the primary key is found.
        $update = $this->values->{field::primary}==="" ? false: true;
        switch($update){
            //
            //Compile the UPDATE statement
            case true:
                //
                //Compile teh primary key
                $primarykey = $this->values->{field::primary};
                //
                //Compile the update sql statement
                $sql = $this->compile_update_sql($primarykey);
                break;
            //    
            //Compile the INSERT statement; the record and sql insert statements 
            //have similar headers but different values section
            case false:
                //
                //Compile the insert values section; this depends on whether the
                //caller is a record or an sql
                $arr= $this->get_insert_values();
                //
                //Return the array keys as a list of comma separated values
                //demarcated with double backticks
                //
                //Get the array keys
                $keys = array_keys($arr);
                //
                //Backtick quote the keys
                $keys2 = array_map(function($key){return "`$key`"; }, $keys);
                //
                //Convert the key to a comma separated list
                $fnames = implode(", ", $keys2);
                //
                //Return the values as a list of comma separated values demarcated
                //with single quotes
                //
                //Convert to a comma separated list. Remember that the values 
                //ara already quoted
                $values = implode(", ", $arr);
                //
                //Compile the insert header section
                $sql = "INSERT INTO `$this->tname` ($fnames) VALUES ($values)";
                //
                //Insert the body
                break;
        }
        //
        //
        //Get the current databse
        $dbase = $this->get_dbase();
        //
        //Execute the sql; report any error
        if (!$result = $dbase->conn->query($sql)) {
            die($sql . "<br/>" . $dbase->conn->error);
        }
    }
    
    //Returns true and binds the the primary key of this record if it is valid;
    //otherwise it returns vfalse
    function try_primarykey_value(&$primarykey){
        //Compile teh primary key
        if (isset($this->values->{field::primary})){
            //
            $primarykey = $this->values->{field::primary};
            return true;
        }
        else{
            return false;
        }
    }
    
    //Returns an array of values to insert to a database, indexed by teh field 
    //name
    function get_insert_values(){
        //
        //Statring with an empty array....
        $arr = [];
        //
        //...collect the given records's values in a comma separated list
        foreach($this->fields as $field){
            //
            //Exclude fields that cannot be modified, e.g., primary keys and 
            //derived fields
            if ($field->is_modifiable()){
                //
                //Only valid values are inserted to the database
                if ($field->try_writable_value($this->values, $fvalue)){
                    //
                    //Add the leading comma and compile the header; the fvalue is
                    //already quote delimited if it is not null
                    $arr[$field->name] = $fvalue;
                }
            }
        }
        //
        //Return the data collection
        return $arr;
    }
    
    //Fill this record with the values resulting from executing this record's sql
    //statement with the given condition.
    function fill($condition){
        //
        //Add the condition to the base sql to get the full sql
        $fullsql = "$this->stmt WHERE $condition";
        //
        //Execute the sql statement to get a mysqli::result
        if (!$result = $this->dbase->conn->query($fullsql)) {
            //
            //Error
            throw new Exception($fullsql. "<br/>" . $this->dbase->conn->error);
        }
        //
        //Fetch the only row (as an object for for compatibilty with javascript
        //as JS deos not undertand PHP's index arrays)
        $values= $result->fetch_object();
        //
        //The values cannot be empty. Yes it can. But. T
        if (!$values){
            //
            throw new Exception("Requested data with condition '$condition' not found");
        }
        //
        //Set the values property
        $this->values = $values;
    }
    
    
    //Compile the update sql of this record using the given primary key
    function compile_update_sql($primarykey){
        //
        //Compile the update header section of the statement
        $sql = "UPDATE `$this->tname` SET ";
        //
        //Set the leading comma separator to nothing; it will be updated later
        $comma = "";
        //
        //Compile the comma separated list of values to update
        foreach($this->fields as $fname=>$field){
            //
            //Exclude fields that cannot be modified, e.g., primary key field and
            //derived fields
            if ($field->is_modifiable()){
                //
                //Define a writable field value
                $fvalue = "";
                //
                //Only valid values are updated
                if ($field->try_writable_value($this->values, $fvalue)){
                    //
                    //Add the leading comma and compile the header. The field's 
                    //value is already quote delimited or is null
                    $sql.= "$comma `$fname` = $fvalue";
                    //
                    //Update the comma separator
                    $comma = ", ";
                }
            }    
        }
        //
        //Compile the write footer section
        $sql.=" WHERE `$this->tname` = $primarykey";
        //
        return $sql;
    }            
    
    
    //Display a normal record by looping through all its fields and displaying 
    //each one of them in the requested style.
    function display_data(page $page, layout $layout, mode $mode, driver $sql=null){//record
        //
        //Open the record tag name
        echo "<$layout->record_tag_name ";
        //
        //Display the record attributes; the parent sql carries data on the 
        //identification index of the sql.
        $this->display_record_attributes($page, $sql);
        //
        //Close the recrod attributes
        echo ">";    
        //
        //Add the checkbox cell for every entry to support selection of
        //records for e.g., merging purposes. It's id is simply the name
        //'check' and on click it should gravitate current row to the top
        echo "<$layout->field_tag_name";
        //
        //Show the checkbox if needed; by default it is not needed
        $page->hide_check_box();
        //
        echo ">";
        //
        //On clicking the check box, call the gravitate function associated 
        //with the javascript expression of the given page
        echo " <input type='checkbox' id='check' onclick='$page->jsxp.gravitate(this);'/>";
        //
        //Close the checkboc field tag
        echo "</$layout->field_tag_name>";
        //
        //Display the content; that depnds on the page
        $page->display_record($this, $layout, $mode);
        //
        //Close the record tag name
        echo "</$layout->record_tag_name>";
        
    }
    
   
    //Display thh attributes of a this record
    function display_record_attributes(page $page, sql $parent=null) {
        //
        //Set the id of table row -- that depends on whether the page's index
        //is defiend or not. The index is the name of the field that supplies 
        //data to the id attribute of the recprd
        //The id attrubute is needed for hreferencing purposes.
        if (isset($page->index)){
            //
            //Get the name of the index field
            $fname = $page->index;
            //
            //The id attribute of any table record, which must be provided, is 
            //needed for hreferencing the tr. The name of the property that
            //houses the name of the id is this.index
            if (isset($this->values->$fname)){
                echo " id='" . $this->values->$fname . "'";
            }
        }
        //
        //The on-click event should evoke the record selection base on the
        //javsecript expression of the given page
        echo " onclick='".$page->jsxp.".select_dom_record(this)'";
        //
        //Set the primary key -- if it is available
        if (isset($this->values->{field::primary})){
            echo " primarykey='".$this->values->{field::primary}."'";
        }
             
    }
    
}

//The root sql is special in that:-
//- it is the parent ancestor of all mutall sqls
//- It sets the string version of an sql object
//- It implements the getters and setters of all protected properties in the
//  inheritance chain
//- It can drive a page, just like a record
class sql extends driver{
    //
    //The database from which this sql is derived is protected as we dont need a 
    //json version of it. We use the get and accessors access it.
    //(Why was I forced to make this public?)
    public $dbase;
    //
    //The joins that constitute the From clause of an sql statement. Their use
    //expires once the sql statement is constructed, so they dont need jsoning
    protected $joins = [];
    //
    //The where clause as a boolean expression for the driving the sql 
    //statement
    public $where;
    //
    //The statement that defines this direct sql. If not provided, it is 
    //compiled from the sql clauses
    public $stmt;
    //
    //The Mutall sql object models the sql statement. If the statement is given 
    //directly, we use it as it is; otherwise we derive derived it using the sql 
    //data initializer that is specific to the sql. If the fields are given, we
    //respect them; ojerwise we derived them from the statement.
    function __construct(dbase $dbase, $stmt=null, $fields=null) {
        //
        $this->dbase = $dbase;
        //
        $this->stmt = $stmt;
        //
        //Initialize the inherited page driver
        parent::__construct($fields);
        //
        //Set the properties of this sql, i.e., fields and joins
        $this->initialize_sql_data();
    }
    
    //Returns true if this sql yields no data
    function is_empty(){
        //
        //Execute the sql
        $results = $this->execute();
        //
        //Fetch teh first record
        $result = $results->fetch_assoc();
        //
        $is_empty = $result ? false: true;
        //
        //Close the result
        $results->close();
        //
        return $is_empty;
    }
    
    //Write the data generated by this sql to a database. Not just any sql can 
    //be written to a database, only those derived that extends sql_table, e.g.,
    //sql_edit.
    //This functionality is not implemented for this version. When it will, it
    //allow saving of multiple records to a database, all sharing the same 
    //header. Binding of values to parameters will be a plausible. This version
    //wiill need get_writeble_data(), as opposed to the try_writable_data 
    //option used by the record class
    function write_data(){
        //
        throw new Exception("Writeing sql data to a database is not yest implementsd");
    }
    
    
    //The default initialization of an sql is needed only if the fields of this
    //sql are not given
    function initialize_sql_data(){//sql
        //
        //Building of fields is not necessary if the fields are already
        //defined
        if (isset($this->fields) && !is_null($this->fields)){
            //
            return;
        } 
        //
        //Derive the fields from this sql's statement
        //
        //We assume that the statement must be set
        //
        //Execute this query 
        $query = $this->execute();
        //
        //Retrieve the columns. The resullyig array is not indexed
        $fields = $query->fetch_fields();
        //
        //Initialize this sql's fields
        foreach($fields as $field){
            /*
            Indexes of the fetched field
            name	The name of the column
            orgname	Original column name if an alias was specified
            table	The name of the table this field belongs to (if not calculated)
            orgtable	Original table name if an alias was specified
            max_length	The maximum width of the field for the result set.
            length	The width of the field, in bytes, as specified in the table definition. Note that this number (bytes) might differ from your table definition value (characters), depending on the character set you use. For example, the character set utf8 has 3 bytes per character, so varchar(10) will return a length of 30 for utf8 (10*3), but return 10 for latin1 (10*1).
            charsetnr	The character set number (id) for the field.
            flags	An integer representing the bit-flags for the field.
            type	The data type used for this field
            decimals	The number of decimals used (for integer fields)*/
            //
            //Create a new column from the given standard field of a fetched 
            //record, leaving out the optional fields
            $col = new column(
                    //
                    //Use the oroginal table of as the tname
                    $field->orgtable, 
                    //
                    $field->name,
                    //
                    //Pass the restof the field
                    $field);
            //
            //Set this query's fields indexed by the field name
            $this->fields[$field->name]=$col;
        }
    }
   
    //Display the Sql (driver data) using the given style. An sql has no parent driver.
    //In contrast, the parent of a record is an sql and that of a field is a record
    function display_data(page $page, layout $layout, mode $mode, driver $parent=null) {//sql
        //
        //Output the header tags if they are needed; they are not needed if we
        //are displaying data to extend (rowwise) an existing table, e.g., when 
        //implementing the scrolling function 
        if (!$page->body_only){
            //
            //Output the body tag
            echo "<$layout->envelop_tag_name";
            //
            //Close the body argument tag
            echo ">";
            //
            //Output the open body tag, e.g., tbody for the tabular layout
            echo "<$layout->body_tag_name>";
            //
            //Display a header; that depends on the layout. Labeled pages
            //dont have a header. Consider doing away with layout as an object
            //in favvor of layout_type -- which a simple data type that is easily
            //shared with the client. This means paraphrasing this instruction 
            //to:
            //$this->display_layout_header($layout, $page)
            $layout->display_layout_header($this, $page);
        }
        //
        //Create a new record that shares the same reference table name as this Sql. 
        //The optional values is left out, until we execute the sql and start stepping
        //through it to fill them.
         $record  = new record($this->fields, $this->dbase);
        //
        //Execute the sql to get a result. Pass the caller page.
        $result = $this->execute($page);
        //
        //Fetch each row as a php record constructed using the given parameters
        //Pass the layout paramaters so we can tell where we came from??
        while ($values= $result->fetch_object()) {
            //
            //Now set the record's values
            $record ->values = $values;
            //
            //Display the record in a the requested layout and mode. The parent
            //of the record is this sql
            $record->display_data($page, $layout, $mode, $this);
        }
        //
        //Output the closing header tags -- if they are needed. See the comment
        //above
        if (!$page->body_only){
            //
            //Output the close body tag, e.g., tbody for tabular layouts
            echo "</$layout->body_tag_name>";
            //
            //Close the body
            echo "</$layout->envelop_tag_name>";
        }
    }
    
    //
    //Retuns the fields to be used for retrieving data for this sql. For ordinary
    //sqls, these are the normal fields. For sql edit these are the basic fields
    function data_fields(){
        //
        return $this->fields;
    }
    
    
    //
    //Convert this sql into a select string statement
    function get_stmt() {
        //
        //We assume that the fields of this sql are all basic fields, so the 
        //fvalue property is valid????
        $exps = [];
        //
        //Get the fields to use for retrieving data for this sql
        $fields = $this->data_fields();
        //
        //Compile the expressions
        foreach($fields as $key=>$field){
            //
            //Get the alias; there is no alias if a field does not have a name
            $alias = $field->alias();
            //
            //Having lots of problems with this. What problems?
            $fvalue = $field->fvalue->__toString();
            //
            $exps[$key] = $fvalue.$alias;
        }
        //
        //Convert the list of aliased field expressions into a comma separated 
        //list of strings
        $a = implode(", ", $exps);
        //
        //Let $b be the required join expression
        //
        //Starting with the reference table name, formulate a join expression 
        $b = "`$this->tname`";
        //
        //Walk through the joins to compile the desired From clause
        foreach ($this->joins as $join) {
            $b = "(" . $b . (string) $join . ")";
        }
        //Let $c be where clause
        $c = isset($this->where) ? " WHERE " . $this->where : "";
        //
        //Compile the full statement, including the select condition
        return "SELECT $a FROM $b $c";
    }

    //Define the getter and setter functions for this sql's protected properties
    function __get($prop){
      return $this->$prop;  
    }
    function __set($prop, $value){
      $this->$prop = $value;  
    }

    //To update the protected joins, the getter and setter is not enough. Hence
    //this simple function
    function update_joins($which, $join){
        $this->joins[$which]=$join;
    }
    
    //Execute this sql (on the given page) to get a result. The page imposes
     //further conditions and limits on the data that is returned by this sql
    function execute(page $page=null) {
        //
        //Compile the full sql statement
        //
        //Get the base sql statement, i.e, without any conditioons. In future 
        //this will be a pre-defined view named after this query's reference table
        $base = $this->stmt;
        //
        //Get the where clause
        $where = isset($this->where) ? " WHERE " . $this->where : "";
        //
        //Set the page limit and offset
        //
        //By default, there is no page limit
        $limit="";
        //
        if (isset($page->limit)){
            //
            //Define the offset if it is specified
            $offset= isset($page->offset) ? " OFFSET $page->offset": "";
            //
            //Define the full limit statement
            $limit = " LIMIT $page->limit$offset";
        }
        
        //
        //The full statement includes the where and limit clauses
        $stmt = "$base$where$limit";
        //
        //Execute the sql statement to get a mysqli::result
        if (!$result = $this->dbase->conn->query($stmt)) {
            //
            throw new Exception($stmt . "<br/>" . $this->dbase->conn->error);
        }
        //
        //Return the result
        return $result;
    }
    
    //The string version of any sql object is its sql statement -- if its null;
    //otherwise derive it
    function __toString() {
        return isset($this->stmt) ? $this->stmt: $this->get_stmt();
    }

}

//The SqlClause is constructed from parts that constiutes the clauses of 
//an sql statement. It has the following special features:-
// - It overrrides the initialize_sql_data to prevent the default version
// - It is typically used for formulating an sql by stringing together 
//components from other sql
class sql_clauses extends sql {
    
    //The critical bits of an sql are the fields and joins; the joins require
    //a reference table, so that if they are empty, then the From clause
    //is formulated from it. The where is optional
    function __construct($dbase, $tname, $fields, $joins, $where=null) {
        //
        $this->fields = $fields;
        $this->joins = $joins;
        $this->tname = $tname;
        $this->where = $where;
        //
        //Initialize the root sql without a statement
        parent::__construct($dbase, null, $fields);
    }
    
    //You need to override the default data initializer -- otherwise the field
    //will be replaced with those of teh executed sql -- rather than the intedned
    //ones
    function initialize_sql_data(){}//sql_clauses
    
}

//sql to support editing of table records. It is driven by all the columns 
//defined by the reference table. Primary and foreign key columns are 
//presented in more friendly ways than the standard fashion.
class sql_edit extends sql{

    //The sql edit has features of a table: primary key, criteria and id fields
    use table_;
    //
    //The reference table that is driving this sql
    public $tname;
    //
    //The basic fields of this sql are obtained by spliting (mixed basic and
    //compound) fields into basic fields used for retrieving data for the
    //sql. They are needed for retriving the necessary data for an edit sql
    public $basicfields;
    //
    //Are you retrieving all the fe;ds, or on;y the primary key one?
    public $all_fields;
    
    //
    //The reference table associated with this sql. Make it public in order to 
    //export using json. We need this in order to acccess the indentification 
    //indices of the reference table taht are required for writing to the database
    public $reftable;

    //The reference table is key to this process; by default sql_edit is composed
    //of all the fields of the reference table. Otherwise it returns only the
    //primary key column
    function __construct(dbase $dbase, $tname, $all_fields= true) {
        //
        //Set the fields provided in the arguments
        $this->dbase = $dbase;
        $this->tname = $tname;
        $this->all_fields= $all_fields;
        //
        //Initialize the fields and joins components by evoking the root sql;
        parent::__construct($dbase);
        //
        //Set the basic fields by spliting the primary and forein key fields.
        //These will be used to eformulate the sql needed for retreiveing data 
        //for this edit sql
        $this->basicfields = $this->get_basicfields();
        //
        //Set this sql's statement; its needed for furthe use and has to be 
        //set after teh basic fields are. 
        $this->stmt = $this->get_stmt();
    }
    
    //Build both the fields and joins of this sql_edit. The process is driven 
    //by all the fields of the this sql, if provided; otherwise by those of the 
    //reference table.
    function initialize_sql_data() {//sql_edit
        //
        //Set the reference table object from this sql's reference table name
        $this->reftable = $this->dbase->get_table($this->tname);
        //
        //If the fields are provided, we use them; otherwise we use the fields
        //of the reference table
        $fields = is_null($this->fields) || !isset($this->fields) ? $this->reftable->fields : $this->fields;
        //
        //Decide if we need to return all fields or just the primary key one
        if ($this->all_fields){
            //
            //Use all the given fields to derive those of this this sql_edit
            foreach ($fields as $col) {
                //
                //Dependng on the column type, initialize this sql_edit. This 
                //is the importance of column extensions. Normal and foreign key 
                //columns contribute fields and joins differently.
                $col->initialize_edit_sql($this);
            }
        }
        //
        //Otherwise, we only need the primary key column
        else{
            //
            //Get the primary key column; it has the same name as the reference 
            //table.
            $col = $fields[$this->tname];
            //        
            //Use it to initialize the sql data. This the importance of 
            //column extensions. Normal and foreign key columns contribute
            //fields and joins differently
            $col->initialize_edit_sql($this);
        }
    }
    
    //Returns a new record from this sql edit statement. An empty record is 
    //returned if the values argument is ommited
    function get_record(stdClass $values=null){
        //
        //Use the sql_edit properties to create a new record. Is it fair to
        //conclude that a record is an sql_edit extended with values, so that 
        //its construtor matches new record(sql_edit, values) ?? What about the
        //case of a reccord not based on an sql, for instance, during login?
        //
        //Create the record from this sql_edit
        $record  = new record($this->fields, $this->dbase, $this->tname, $this->reftable, $this->stmt, $values);
        //
        //return the new record
        return $record;
    }  
    
    //
    //Retuns the fields to be used for retrieving data for this sql. For sql edit
    //these are the basic fields
    function data_fields(){
        //
        return $this->basicfields;
    }
        

    //Split the mixed fields of this sql querry into all basic fields for the 
    //purpose of constructing a suitable statement for fetching data of sql edit
    function get_basicfields() {
        //
        //Collect all the basic fields of this sql by splitting its compound 
        //fields into subfields -- strating with the empty basic fields. 
        $basicfields = [];
        foreach ($this->fields as $field) {
            //
            //Split the field into subfields. Normal fields are not affected
            foreach ($field->split() as $subfield) {
                
                //Add the subfield to the basic fields collection
                $basicfields[$subfield->name] = $subfield;
            }
        }
        //
        return $basicfields;
    }

    //Override the default string statement of an sql, so that basic fields are
    //used for constructing the statement (rather than the actual fields). This
    //is also important in order to involve the where clause wihich is added
    //late to sql edit
    function __toString() {
        //
        //Compile a sql using clauses and the edit sql's basic fields. Remmeber
        //that basic fields (rather mixed ones) are the ones needed for 
        //constructing an sql. The following arguments are needed by the sql_clause
        //constructor:-
        //$dbase, $tname, $fields, $joins, $where
        $sql = new sql_clauses($this->dbase, $this->tname, $this->basicfields, $this->joins, $this->where);
        //
        //Add the limit clause; the limit is the number of records to retuen
        //Offset is the starting point
        $limit = isset($this->limit) ? " LIMIT $this->limit OFFSET $this->offset" : "";
        //
        //Compile the full sql
        $fullsql = "$sql $limit";
        //
        //Return the full str string version
        return  $fullsql;
    }

}

//The sql_hint is an sql that comprises of only the identfication and criteria/friendly 
//raw fields of some reference table. It is used to derive the id and criteria fields
//of sql_selector (through concatenation) that is in turn used to extend the sql_edit 
class sql_hint extends sql {
    //
    //The relational distance of this criteria sql is the number of foreign key 
    //fields between some reference table and that of this sql. It is used for
    //formulating inner joins so that neeare joins are done before later
    //ones.
    public $rdistance;

    //The reference table guides the construction of the sql columns and their
    //supporting joins
    function __construct($dbase, $tname, $rdistance) {
        //
        $this->tname = $tname;
        $this->rdistance = $rdistance;
        //
        //Initialize the parent sql
        parent::__construct($dbase);
    }

    //Initialialize both fields and joins of this criteria sql. The process is 
    //driven by criteria columns, i.e., the identification and friendly fields of 
    //the reference table.
    function initialize_sql_data() {//sql_hint
        //
        //Set the reference table from the table's name
        $this->reftable = $this->dbase->get_table($this->tname);
        //
        //Use the criteria columns of the reference table to initialize the sql 
        //data for this extension sql
        array_walk($this->reftable->hint_cols, function($col) {
            //
            $col->initialize_hint_sql($this);
        });
    }

}

//The sql_selector query is used to extend the table sql so that foreign keys can be 
//befriended. It has the following characateristics: 1) it can be (left) joined 
//to the reference table of the Edit sql, 2) It has 3 columns: the primary,
//the criteria and the id columns. To enable (1), therefore, the sql 
//must implement the table interface
class sql_selector extends sql implements table {
    //
    //A selector query must be named, so that it can be used as a subquery
    //in the the more complex queries, eg., sql_edit
    public $name;

    //Use the shared implementations of some of the functions defined in the 
    //table interface
    use table_;

    //
    function __construct($dbase, $tname) {
        //
        $this->tname = $tname;
        //
        //A selector query must be named, so that it can be used as a subquery
        //in the more complex queries, e.g., sql_edit
        $this->name = $tname."_ext";
        //
        //Initialize the parent sql
        parent::__construct($dbase);
    }

    //Initialize the required 3 fields -- primary, id and criteria -- of 
    //this sql as well as joins needed to support formulation of these fields
    //The expected string version of this sql should be look like:-
    //
    //select $primaryfield as primary, concat($hints) as criteria,  concat($ids) as id from
    //$joins
    //
    //The primary key column is used for supporting record updates, the id 
    //for hreferencing the records and the criteria for driving record selection 
    function initialize_sql_data() {//sql_selector
        //
        //Set the reference table from the table name
        $this->reftable = $this->dbase->get_table($this->tname);
        //
        //SET THE PRIMARY FIELDS
        //
        //Formulate the desired primary key field 
        $primaryfield = $this->reftable->primary_field;
        //
        //Add the primary field to this sql's fields
        $this->fields[field::primary] = new field(field::primary, $primaryfield->fvalue);
        //
        //Get a) raw id and criteria fields and b) required joins from sql IdHint.
        //Assume the relational distance is 0
        $sqlHint = new sql_hint($this->dbase, $this->tname, 0);
        //
        //SET THE HINT FIELDS
        //
        //Concat all the fields of $sql to get a new expression
        $hintvalue = new expression_concat($sqlHint->fields);
        //
        //Create a basic field criteria named criteria
        $hintfield = new field(field::output, $hintvalue);
        //
        //Add the criteria to this sql's fields using the criteria index
        $this->fields[field::output] = $hintfield;
        //
        //SET THE ID FIELDS
        //
        //Filter the id fields from the criteria cases
        $idfields = array_filter($sqlHint->fields, function($idfield) {
            return $idfield->field_is_id($this->dbase);
        });
        //
        //The fvalue expression of the id field is the concatenation of the id 
        //fields of the criteria sql
        $idvalue = new expression_concat($idfields);
        //
        //Create the id field for this sql
        $idfield = new field(field::id, $idvalue);
        //
        //Add it to the sql_selector using the id index
        $this->fields[field::id] = $idfield;
        //
        //SET THE JOINS
        //
        //Set this sql's joins to those of $sqlHint
        $this->joins = $sqlHint->joins;
    }

}

//table is an abstract that is extended by ordinary database tables and other
//derived versions. It is used to support the "From $table" clause of an sql 
//where $table is a standard database table or an sql that can participate
//in a From clause
interface table {

    //Examples of a table sql values are expressions used in the From clause.
    //SELECT ... FROM "client" WHERE ....
    //SELECT ... FROM "(SELECT .....) AS zone__id" WHERE ....
    //The quoted bits are table expressions
    function value();
}

//The table_ trait contains implementations of functions that are shared by 
//clases the implement the table interface
trait table_ {

    //The sql value of an sql that extends a table is used in the From clause
    //and has the form:-
    //(select .....) as zone_fk
    function value() {
        return "(" . (string) $this . ") AS `" . $this->name . "`";
    }
}

//The sql_table corresponds to a database table and implements the table 
//interface so that it can take part in a From clause of the select 
//statement). Its special in that:- 
//- Its fields are derived from the information PageDatabases of the given table
//- It supports an identification index
class sql_table extends sql implements table {
    //
    //Every table has a table name
    public $tname;
    //
    //Use the commonly immplemented functions of the table interace that is shared
    //by all sqls that can take part in a join
    use table_;

    //Indices is one or more sets of fields that are used for uniquely 
    //identifying arecord.(How do we identify the best index?)
    public $indices;
    
    //Hint columns is a unique combination of columns that are data in this table
    //both friendly and uniquely defined. These columns are used for:-
    //- constructing the identifier needed for hreferencing purposes
    //- implementing a criteria search method to be used for generally locating 
    //records
    public $hint_cols;
    
    //
    //Track the primary field of this table; it is set during table creation and
    //used for fotmulating the sql_selector query
    public $primary_field;
    
    //A table constructor; If the database and table name parameters are 
    //not specified, we will try reading them from the last poated page. This
    //parameter free mode of calling is expected when we activate a mutall
    //object from a static version. It therefore should be implemenetd for all 
    //basic mutall objects.
    function __construct($dbase=null, $tname=null) {
        //
        //Use the given arguments or read them from the last posted page
        //
        //Create a mutall object to access shared functions
        $mutall = new mutall();
        //
        //Get teh last posted page
        $page_ = $mutall->get_posted_data();
        //
        $this->dbase = is_null($dbase) ? $mutall->get_dbase(): $dbase;
        $this->tname = is_null($tname) ? $page_->tname: $tname;
        //
        //The select statement of a table is simply
        $stmt = "select * from `".$this->tname."`";
        //
        //Initialize the root table; this process will evoke the initializetion 
        //of the fields
        parent::__construct($this->dbase, $stmt);
        //
        //Set the identification indices of this table using its fields
        $this->indices = $this->get_indices();
        //
        //Set the criteria columns
        $this->hint_cols = $this->get_hint_cols();
    }

    //The fielda of a tavle are initialized fro mysql's information PageDatabases
    // The source of data are 3 information PageDatabases tables:- 
    //key_column_constraints provide foreign key columns
    //table_constraints helps us to isolate the foreign key constraints
    //columns is a list of all the table's column
    function initialize_sql_data(){//sql_table
        //
        // Select the foreign key columns. The referenced table andmcolumn names
        // come from the key column usage table. The type of constraint, foreign,
        // is foun in the constrains table
        $foreign = "select"
            ." `usage`.`column_name`,"
            ." `usage`.`table_name`," 
            ." `usage`.`table_schema`,"
            ." `usage`.referenced_table_name as fk_table_name," 
            ." `usage`.referenced_column_name as fk_col_name"
        //        
        ." from information_schema.`KEY_COLUMN_USAGE` as `usage`"
             ." inner join information_schema.`TABLE_CONSTRAINTS` as const" 
             ." on `usage`.`constraint_name`=const.`constraint_name`"
                ." and `usage`.`table_name`=const.`table_name`"
                ." and `usage`.table_schema=const.table_schema"
        ." where const.constraint_type='foreign key'";
        //
        //Returns all the PageDatabases columns and add information to do with foreign
        //key constraints      
       $cols = "select "
            //
            //Rename column name to simply namme
            ." field.column_name as `name`,"
            //
            //Let teh table name be part of the field description   
            ." field.table_name as `tname`,"
            //   
            ." field.is_nullable," 
            //
            //Rename data type
            ." field.data_type as `type`," 
            //
            //Rename length
            ." field.character_maximum_length as `length`," 
            ." field.column_type," 
            //
            //Rename column key   
            ." field.column_key as `key`," 
            //
            //Rename comment   
            ." field.column_comment as comment,"
            
            ." `foreign`.fk_table_name," 
            ." `foreign`.fk_col_name"
        //       
        //From the left join of the field and the foreign tables so that all
        //coluns with and without forein key refeences are returned
        . " from information_schema.columns as field left join ($foreign) as `foreign` on
            `foreign`.column_name = field.column_name and 
            `foreign`.`table_name` = field.`table_name` and
            `foreign`.table_schema = field.table_schema"
        //
        //Limit the fields to relevant database and table
        . " where "
            . " field.table_schema = '" . $this->dbase->dbname . "' 
            and field.table_name = '{$this->tname}'";
        //
        //Now use the sql to query the database (connection). Abort the process in case 
        //of error -- echoing the error message.
        if (!$result = $this->dbase->conn->query($cols)) {
            die($cols . "<br/>" . $this->dbase->conn->error);
        }
        //
        //Construct fields from the fetched description as an stdClass object
        while ($description= $result->fetch_object()) {
            //
            // Retrieve the column name
            $name = $description->name;
            //
            //Use the column type and the presence (or absence) of foreign key 
            //fields to classify teh edscription to either foreign primary etc.
            $description->classname = $this->classify_column($description);
            //
            //Set the table name of column being described as it is not part of 
            //the description
            $description->tname = $this->tname;
            //
            //Activate the description -- a process called
            //mutallify
            $col = (new mutall())->activate($description);
            //
            //Register this column as the primary key one for this table
            if (get_class($col)==="column_primary") {
                $this->primary_field = $col;
            }
            //
            //Add it to the field collection of felds of this table
            $this->fields[$name] = $col;
        }
        
    }
    
    //Use the column type and the presence or absence of foreign key 
    //fields to classify this column as either foreign, primary or ordinary 
    //column
    function classify_column($description) {
        //
        //Recognize a primary key column via the 'pri' keyword
        if ($description->key === 'PRI') {
            //
            //Return a new primary key using this same table
            return "column_primary";
        }
        //
        //Recognize foreign key columns
        elseif (isset($description->fk_table_name)) {
            //
            //Compile the foreign key table and columns names into a single object
            $foreign = new stdClass;
            $foreign->table_name = $description->fk_table_name;
            $foreign->column_name = $description->fk_col_name;
            //
            //Remove those fields from the edscription 
            unset($description->fk_table_name);
            unset($description->fk_col_name);
            //
            //Add tge foreign key column
            $description->foreign = $foreign;
            //
            //Return a foreign key column
            return "column_foreign";
        }
        //
        //Return a new ordinary column
        else {
            return "column";
        }
    }


    
    //Returns the first index columns of this table as the list of columns 
    //that are derived from the first identification index. (The notion of the
    //best index is not applicable here as any index can be used to drive the
    //record identification process)
    function first_index_cols() {
        //
        //Collect all the keys of this table's indices
        $keys = array_keys($this->indices);
        //
        //Pick the first key; if this fails, then teher is no identificaion index
        try{
            $key = $keys[0];
        } catch(Exception $e){
          //
          throw new Exception("No identification index is found");   
        }
        //
        //Now get the first index
        $index = $this->indices[$key];
        //
        //A index is an array of index colum names; map them to actual columns
        $cols = array_map(function ($colname) {
            return $this->fields[$colname];
        }, $index);
        //
        //To ensure that the returned columns are column_name indexed
        return array_combine($index, $cols);
    }

    //Return the criteria columns, i.e, unique combination of descriptive and
    //identification fieields
    function get_hint_cols() {
        //
        //Filter from this table's columns those that are descriptive
        $descriptives = array_filter($this->fields, function($col) {
            //Get the column name
            $name = $col->name;
            //
            //Descriptive columns are named are name,  description or have a 
            //name            //suffix
            $filter = ((substr($name, -4) == "name") || ($name == "description")) ? true : false;
            //
            return $filter;
        });
        //
        //Let $c be the combination of indexing and descriptive columns
        $c = array_merge($this->first_index_cols(), $descriptives);
        //
        //Remove duplicates. Get the __toString to work correctly, i.e, the 
        //array unique function requires us to convert the columns to strings
        $d = array_unique($c);
        //
        //return the combination
        return $d;
    }

    //The sql value of a standard table, as required in a From clause is simply
    //the table name
    function value() {
        return "`{$this->tname}`";
    }

    //Collect this table's identification indices
    private function get_indices() {
        //
        //Select all the identification indices of this table
        $sql = "select 
                constraint_name 
            from information_schema.TABLE_CONSTRAINTS 
            where table_schema='" . $this->dbase->dbname . "'
                and constraint_type='unique'
                and table_name='{$this->tname}'";
        //
        //Now use the sql to query the database (connection). Abort the process in case 
        //of error -- echoing the error message.
        if (!$result = $this->dbase->conn->query($sql)) {
            die($sql . "<br/>" . $this->dbase->conn->error);
        }
        //
        //The following are the two allowed index name prefixes
        $prefixes = ["id", "identification"];
        //
        //Start with an empty list of indices
        $indices = [];
        while ($resulttype = $result->fetch_assoc()) {
            //
            //Get the name of the index
            $xname = $resulttype['constraint_name'];
            //
            //Only indices named following the mutall convention are considered
            if ($this->valid_xname($xname, $prefixes)){
                //
                //Set the named index to all her index column names
                $indices[$xname] = $this->get_index_fields($xname);
            }
        }
        //
        //Return the indices
        return $indices;
    }
    
    //Define the test for a valid index name
    function valid_xname($name, $prefixes){
        //
        //Step through the allowed prefixes
        foreach($prefixes as $prefix){
            //
            //If there is a direct match then that is a valid 
            //identification index
            if ($name ===$prefix) {return true; }
            //
            //Get the name suffix
            $suffix = substr($name, strlen($prefix));
            //
            //If the suffix numeric then the index name is valid
            if(is_numeric($suffix)){
                return true;
            }
        }
        //
        //This is not an identication index
        return false;
    }

    //Return all the index column names of the named index
    private function get_index_fields($xname) {
        //Select column names  of the named index
        $sql = "select  
                column_name 
            from information_schema.STATISTICS 
            where table_schema='" . $this->dbase->dbname . "'
            and index_name ='$xname'
            and table_name='{$this->tname}'";
        //
        //Now use the sql to query the database (connection). Abort the process in case 
        //of error -- echoing the error message.
        if (!$result = $this->dbase->conn->query($sql)) {
            die($sql . "<br/>" . $this->dbase->conn->error);
        }
        //
        //Start with an empty list of index fields
        $xfnames = [];
        while ($resulttype = $result->fetch_assoc()) {
            //
            //Get the name of the column
            $colname = $resulttype['column_name'];
            //
            //Push the column name into the array
            array_push($xfnames, $colname);
        }
        //
        //Return the indexing column names
        return $xfnames;
    }
}

//The tabular layout is used by all pages that need to present data as a 
//standard table.
class tabular extends layout{
    //
    function __construct() {
        //
        $this->field_tag_name="td";
        $this->record_tag_name="tr";
        //
        //This is the equivalent of the tbody in a tabular layout
        $this->body_tag_name="tbody";
        //
        //Envelop is the table equivalent in a tabular layout
        $this->envelop_tag_name="table"; 
        //
        //Initialize the layout system
        parent::__construct(layout::tabular);
    }
    
    //Display the header(section) of a tabular layout
    function display_layout_header(sql $sql, page $page) {
        //
        echo "<tr>";
        //
        //The first header cell is that of a checkbox reserved for selecting 
        //the entire row. Output it only if necessary
        echo "<th ";
        //Show the cehckbocx if needed; by default it is not needed
        $page->hide_check_box();
        echo ">";
        echo "checked";
        echo "</th>";
        //
        //Visit all the fields of this layout's sql and output each one of them 
        //as a "th" element using the local name.
        foreach($sql->fields as $field) {
            //
            //Display the label of this normal feld in a tabular layout. By default, 
            //every field has a label.
            //
            //
            echo "<th";
                        //
            //Hide/show this field -- depending on the page
            echo $field->is_hidden($page) ? " hidden='true'": "";

            echo ">";
            //
            //Show the fild name. In future we look for the correct label in the 
            //field's comment.
            echo $field->name;
            //
            //Close the header
            echo "</th>";
        };
        //
        echo"</tr>";
    }
}

//The label layout format
class label extends layout{
    //
    function __construct() {
        
        $this->field_tag_name="field";
        $this->record_tag_name="record";
        //
        //This is the equivalent of the tbody in a tabular layout
        $this->body_tag_name="records";
        //
        //Envelop is the table equivalent in a tabular layout
        $this->envelop_tag_name="envelop"; 
        //
        //Initialize the parent layout
        parent::__construct(layout::label);
    }
    
    //Display the label of a field
    function show_label_name($fname){
        //
        //Formulate the label tag of the field. The normal style is described 
        //in mutall.css
        echo "<label class='normal'";
        //
        //Output the "for" clause as name of the field; this must match the 
        //id of the input input element being labeled
        echo " for='$fname'";
        //
        //Close the label attributes
        echo ">";
        //
        //Display the label text. In future, use the field's comment as
        //teh source of a more friendly. 
        echo $fname;
        //
        //Close the label
        echo "</label>";
    }
    
}
