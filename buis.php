<?php
//Buis.php is a library of PHP classes that extends the core mutall libray in order
//to implement a broad user interactive system (BUIS). Broad implies that the 
//intended user base is beyond Mutall programmers and includes our clients.
//This need arose from the heavy demands on Mutall programmers by our clients
//once we compuerised their business operations.
//
//Version 2.3.1 addressed the issue of slow response on the remote server.
//The mutall_page->initialize_dtadabes() was introduced to reduce traffic between
//the server and the client

//Include the core Mutall classes
require_once "mutall.php";

//
//Modelling a login page; The justification for this being in a file of its 
//own is to allow other pages, e.g., page_mutall, to access the login/out 
//the functionality implemented by the page_login class. 
class page_login extends page{
    //
    //These are the administrators login credentials for accessing the 
    //information schema. On set up, the site admin will modify these constants
    //with his/her credentials so that the information schema table oon that site
    //can be accessed
    /*
    const username = "mutallco";
    const password = "mutall2015";
    */
    
    const username = "root";
    const password = "";
    
    //
    //The database that drives the mutall home page
    const mutall_data="mutall_data";
    //
    function __construct(querystring $qstring) {
        //
        $this->layout_type = layout::label;
        $this->mode_type = mode::input;
        //
        //Now call the parent page initializer. 
        parent::__construct($qstring);
    }
    
    //The login page is special in that it is driven by a record that is NOT
    //derived from an sql statement executed on a database
    function get_driver() {//page_login
        //
        //Set this page's data component; its of record type.
        //
        //Define the user name and password fields. The mutall field is the 
        //smallest database savable, i.e., data that can be saved as a unit, element
        $fusername = new field("username");
        $fpassword= new field("password");
        //
        //Collect the user login credentials as a list of indexed fields
        $fields = array("username"=>$fusername, "password"=>$fpassword);
        //
        //Create a record using the fields onnly, i.e., without reference to
        //a database and its data sources -- table name or sql statement.
        //But, the Mutall record is an extension of a writeable object. A 
        //writeable must have a database. This is contradictory
        $record = new record($fields);
        //
        //Optional arguments can be supplied after the page creation
        //
        //Create an empty list of the record's values
        $record->values = new stdClass;
       //
        //Look for the login credentials from the session variable
        $login=null;
        //
        if ($this->try_login($login)){
            //
            //Populate the record values with available login credentials
            $record->values->username = $login['username'];
            $record->values->password= $login['password'];
        }
        //
        return $record;
    }
   //Save the login data posted by a client to the special login session 
    //variables (that the search function can reach). The login credentials
    //are posted as a record attached to a page
    function save_login(){
        //
        //Retrieve the posted query string array
        $arr = $this->arr;
        //
        //Ensure that all the credentials are set. (Its an error if any of 
        //them is not). Save them to the session variable
        if (isset($arr['username'])){
            //
            //Save the username
            $_SESSION['login']['username'] = $arr['username'];
        }
        else{
            $this->error("The username not set");
        }
        //
        //Save the password
        if (isset($arr['password'])){
            //
            $_SESSION['login']['password'] = $arr['password'];
        }
        else{
            $this->error("The password not set");
        }
       //
       //Return a null as the extra data; remember that is method is called by
       //the client via ajax
        return null;
    }
    
    //To logout is to simply destroy the session variables
    function logout(){
        //
        session_destroy();
        //
        //Return the ok message to the ajax caller
        $this->ok();
    }
}

//
//Define a php version of a database page. This classs enables interaction 
//with all MySql databases installed on the local host with the mutall_ name 
//prefix.
class page_databases extends page{
    //
    //The dbname is set when the user selects a database (as part of the user 
    //interaction) and not during construction of the page. On entry, this
    //shows us the currently selected database
    public $dbname;
    //
    //The database that is logged in (not necessarily the one selected).
    public $dbase;
    
    //The sql constructed uses administrator credentials (or any others)
    //that allows access to the information schema
    public function __construct(querystring $qstring) {
        //
        //Define the index of this page -- the field name that is used for 
        //constructing the id property of the sql record. The id property
        //is used for hreferencing purposes
        $this->index = "dbname";
        //
        //Unless otherwise specified, the default layout of a database page 
        //is a label
        $this->layout_type = layout::label;
        //
        //Initialize the parent page
        parent::__construct($qstring);
    }
    
    //Define the driver sql for databases from first principles, i.e., using the
    //information schema
    function get_driver_using_informationschema() {
        //
        //Compile the mutall sql for retrieving databases
        //
        //Formulate a query statement for selecting all the mutall databases
        //on the local host server
        $stmt = "select "
                //
                //The schema name is used for indexing
                . " schema_name as ".$this->index
                //
                . " from schemata";
        //
        // Create a new database using the login credentials of an administrator
        //that gets primary data from teh information schems
        $information_schema= new dbase(page_login::username, page_login::password, "INFORMATION_SCHEMA");
        //
        //Create an sql object directly from query statement; this will is used
        //to drive the database initialzation process
        $driver= new sql($information_schema, $stmt);
        //
        //Return the driver
        return $driver;
    }
    
    //Return all the entities of the databases on the server; we use the 
    //administrator's login credentials, so there is nothing to expect from 
    //the client
    function get_entities(){
        //
        //Clear the mutall_data 'serialization' table
        //
        //Open a mutall_data database using the login credentials of an administrator
        //and the mutall database. This means that anyone has unfettered access
        //to the databases. Access to the database contents needs to be controlled
        //after the databases are shown. You say which one you want to log in to.
        $mutall_data = new dbase(page_login::username, page_login::password, page_login::mutall_data);
        //
        //Get the mutall_data database connection
        $mutall_conn = $mutall_data->conn;
        //
        //Delete all the records in in mutall_data.serialization
        if (!$mutall_conn->query("delete from serialization")){
            //
            throw new Exception($mutall_conn->error);
        }
        //
        $stmt = 
                "select entity, entity.name as tname, dbase.name as dbname"
                ." from entity inner join dbase on entity.dbase=dbase.dbase";
        //
        //
        //Execute the entiy selectioon
        $result = $mutall_conn->query($stmt);
        //
        //Test the result
        if (!$result){
            //
            throw new Exception($mutall_conn->error);
        }
        //
        //Fetch all the entities in one go
        $entities= $result->fetch_all();
        //
        return $entities;
    }
   
    
    //Define the driver sql for databases using the responsivess optimized 
    //query stored in mutall_data as table 'database'
    function get_driver() {//page_databases
        //
        //Create a new database that uses the mutall database to access
        //the 'dbase' table
        $mutall_dbase = $this->get_mutall_dbase();
        //
        //This is the sql used to drive the databases display from mutall_data.
        //Remember that the index name for page_databases is dbname.
        $mutall_sql = new sql($mutall_dbase, "select `name` as dbname from `dbase`");
        //
        return $mutall_sql;
    }
}

//The page_database class enables interaction between the user and all the tables 
//of a the current database
class page_database extends page{
    //
    public $dbase;
    //
    //The table name that is selected from a list of available entries. This is 
    //set when the user selects a record via some javascript interface
    public $tname;
    //
    //Use the available user login credentials to create a page that supports 
    //interaction with the given database.
    function __construct(querystring $qstring) {
        //
        //Define the index name, i.e., the name of the field in the following 
        //query that is used for supply data to be regarded as the id property 
        //of the dom records to be displayed in a javascript interface.
        $this->index = "tname";
        $this->layout_type = layout::label;
        //
        //Initialize the parent page with no default values. The page constructor
        //will use the querystring to compile the arguments it requires. By 
        //declaring it here, we make avaialbel the page-specific functionality
        //needed to construct this page. Set the default layout of a page of 
        //tables; the user can override this default via the querystring.
        parent::__construct($qstring);        
    }
    
    //Define the required driver for this page from first principles; its 
    //an sql derived using the information schema
    function get_driver_using_informationschema(){
        //
        // Create a new database using the login credentials of an administrator
        $schema_dbase= new dbase(page_login::username, page_login::password, "INFORMATION_SCHEMA");
        //
        //Prepare to set this page's data property -- the one that dictates the
        //data to be displayed
         //
        //tnames: Query for retrieving system table (not view) names in the 
        //current database
        $tnames_sql= "select"
            //    
            //Substitute the table name with tname
            ." table_name as tname, "
            //
            //The dbname corresponds to the table schema    
            ." table_schema as dbname"
            //
            //The data comes from the information schems    
            ." from information_schema.tables"
            //
            //Only system tables are considered -- not views
            ." where table_type='base table'";
        //
        //descendants: Query for retrieving decsendants
        $descendants_sql = "select "
            //   
            //The parent table    
            ." referenced_table_name as tname,"
            //
            //The dbname matches the constrains schema    
            ." constraint_schema as dbname, "
            //
            //Concantenate the descendants with a comma; let them be members
            ." group_concat(table_name separator ', ') as members"
            //
            //Use the referential constraints table in the information schema    
            ." from information_schema.`REFERENTIAL_CONSTRAINTS`"
            //
            //Group by the table name and dbname
            ." group by constraint_schema, referenced_table_name";
        //    
        //final sql: Use left join to combine the table names and their 
        //descendants
        $stmt = "select "
                //
                ." tnames.tname as $this->index, descendants.members"
                //
                ." from ($tnames_sql) as tnames "
                //
                ." left join ($descendants_sql) as descendants"
                //
                ." on tnames.tname=descendants.tname "
                //
                ." and tnames.dbname = descendants.dbname"
                //
                ." where tnames.dbname = '$this->dbname'";
        //
        //The sql data needed for this page is of the direct statement type. This
        //property is needed for driving the database initialization part that
        //builds the serialized version of sql_edit to improve response
        $driver = new sql($schema_dbase, $stmt);
        //
        //Returnnteh responsive sql as the driver
        return $driver;
    }
    
    //Define the required driver for this page that is optimized for 
    //responsiveness. It used the 'table' and 'database' tables of mutall_data
    function get_driver(){//page_database
        //
        //Create another database that uses the mutall data table to drive this
        //page in order to improve the responsiveness by not deriving sql edit 
        //from first principles
        $mutall_dbase = $this->get_mutall_dbase();
        //
        //Compile an sql that will be more responsive than the previous statement
        //(that works from first principles)
        $responsive_stmt= "select"
                //
                //Remember the index name of a page of tables is tname
                ." entity.`name` as tname, "
                //
                //Show the serialization status; if serialization was erroneeous
                //it will show here
                ." serialization.error as status"
                //
                //Join 'table' and 'database' tables. Consider renaming these to
                //dbase and entity to avoid teh reserved mysql keywords table
                //and database
                ." from (entity inner join dbase on entity.dbase = dbase.dbase)"
                ." left join serialization on serialization.entity=entity.entity"
                //
                //Only tables of the dbname should be returned
                ." where dbase.`name` = '$this->dbname'";
                
        //This is the sql used to drive the databases display from mutall_data.
        $responsive_sql = new sql($mutall_dbase, $responsive_stmt);
        //
        //Returnnteh responsive sql as the driver
        return $responsive_sql;
    }
    
    //Serialize the requesteted entity and send back to the client the 
    //confirmation message
    function serialize_entity($entity=null, $tname=null, $counter=null){//serialize_entity
        //
        //Bind the arguments
        $this->bind_arg('entity', $entity);
        $this->bind_arg('tname', $tname);
        $this->bind_arg('counter', $counter);
        //
        //Open the mutall_data database connection
        $mutall_conn = $this->get_mutall_dbase()->conn;
        //
        //Prepare the statements for inserting the sql  records
        //
        //Compile the sql (for inserting a serialization) in terms of its parameters
        $sql = "insert into serialization(entity, sql_edit, sql_selector, error) values(?, ?, ?, ?)";
        //
        //Prepare the sql and verify that it is valid
        if (!($insert_serialization=  $mutall_conn->prepare($sql))){
        
            throw new Exception($mutall_conn->error);
        }
        //
        //Define the variables to be bound to the sql parameters
        //
        //Define the binary serialized version of a table's sql_edit with all
        //the table's fields in it. Compare this to the selector version below.
        $sql_edit=null;
        //
        //A selector query is a serialilized version of the sql_edit query with 
        //primary key field only.
        $sql_selector=null;
        //
        //Define a variable for holding error messages if serializing was 
        //not successful
        $error=null;
        //
        //Sql bind the variables to their matching parameters
        if (!$insert_serialization->bind_param("ssss", $entity, $sql_edit, $sql_selector, $error)){
            //
            throw new Exception($insert_serialization->error);
        }
        //
        //Create a new user database based on the dbname and the user 
        //credentials. We expect this process to be initialed by the adminstrator
        //so, we use his credentials
        $dbase = new dbase(page_login::username, page_login::password, $this->dbname);
        //
        //Only mutall compliant tables are considered
        try {
            //
            //Select all the fields of a table and set the serialized version 
            //of the fully fielded sql_edit
            $sql_edit = serialize(new sql_edit($dbase, $tname, true));
            //
            //Set the serialized version of the partially fielded sql_edit
            //filt for record selection
            $sql_selector= serialize(new sql_edit($dbase, $tname, false));
        } 
        catch (Exception $ex) {
            //
            //Set the error message
            $error = $ex->getMessage();
        } 
        finally {
            //
            //No execute teh bounderd serialization
            $ok = $insert_serialization->execute();
            //
            //Report error if not ok
            if (!$ok){
                //
                throw new Exception($insert_serialization->error);
            }
        }
            
        //
        //Compile the nofification message
        $notification= array("tname"=>$tname, "dbname"=>$this->dbname, "counter"=>$counter);
        //
        //Return the notification
        return $notification;    
    }
    
}

//
//A page_records models a list of records based on a database table. It extends 
//page-table by being able to condition the display driver for page_table
class page_records extends page_table{
      //
    //The default search criteria of a page is an empty string; the criteria
    //is defined here because it is required by display which is also defined 
    //at this same page level.
    public $criteria="";
    //
    //How to order listed records; by daffault, teh storage order is used
    public $order_by="";
    //
    //The default start position is the first 0-based record
    public $offset=0;
    //
    //The full page limit has to be large enough for scroll bars to 
    //appeare; otherwise the onscroll event will not fire
    const full_limit = 40;
    //
    //The scroll page size is half of teh full page size to increase
    //responsiveness
    const scroll_limit=20;
    //
    //Set the full and scroll page size limits of this page to the defined values
    //This is important because we need to pass the correspponding constants
    //to where they are needed, viz., in the client's javascript environmentse. 
    public $full_limit=self::full_limit;
    public $scroll_limit=self::scroll_limit;
    //
    //The default limit is the full one
    public $limit=self::full_limit;
     
    //Construct a page to display a list of records base on a table
   
    //This method illustrates the Mutall's mechanism of interfacing the PHP and 
    //Javascript objects. The full signature of this method version is:=
    //
    //($criteria=null, $order_by=null, $offset=null, $body_only=null)
    //
    //Note that:-
    //  -   all the arguments have the null default value; 
    //      this allows us to evoke this function without any parameters -- a 
    //      requirement for calling the method from the client's (javascript) side via 
    //      the ajax mechanism. The null value allows us to test whether the argument
    //      was omitted or not. If ommitted then the arguments' data  must 
    //      be supplied via the page's query string passed through the global 
    //      variables, viz., $_POST or $_GET.
    //  -   the arguments are bound to the values in the querystring if ommited 
    //      before they are used within this method
   function __construct(querystring $qstring, $criteria=null, $order_by=null, $offset=null, $body_only=null){//page
        //
        //Bind the arguments of this ajax-evoked method using the querystrng.
        $qstring->bind_arg('criteria', $criteria, $this);
        $qstring->bind_arg('order_by', $order_by, $this); 
        $qstring->bind_arg('offset', $offset, $this, FILTER_VALIDATE_INT);
        $qstring->bind_arg('body_only', $body_only, $this, FILTER_VALIDATE_BOOLEAN);
        //
        //Initialize the inherited page_records system. Pass only the mandatory
        //querystring; the parent constructor will figure out from the querystring
        //how to initialize itself
        parent::__construct($qstring);
    }
    
     //Returns the sql or record that drives the display of this page of records. 
    //This implementation overrides the default page one as the method is special 
    //for page_records; it involves use of unserialized sql_edit
    function get_driver(){//page_records
        //
        //Retrieve the (serialized) sql (data) that was used to construct this page.
        //It is the driver of the parent page_table.
        $sql_edit = $this->get_sql_edit();
        //
        //Mark the hidden fields on this page
        $sql_edit->mark_hidden_fields($this);
        //
        //Add the ehere clause
        $criteria2 = is_null($this->criteria)||$this->criteria==="" ? "" : " WHERE $this->criteria";
        //
        //Add the order by clause
        $order_by2 = is_null($this->order_by)||$this->order_by==="" ? "" : " ORDER BY $this->order_by";
        //
        //Compile the full statement
        $stmt2 = "$sql_edit->stmt$criteria2$order_by2";
        //
        //Formulate the sql that will drives this page, based on the 2nd statement 
        //and the fields of the first statement
        $driver = new sql($sql_edit->dbase, $stmt2, $sql_edit->fields);
        //
        return $driver;
    }
 
    
    //Save this record using the given (old json) values and return the new
    //ones (of the just saved record)
    function save_current_record($jvalues=null){
        //
        //Bind the json argument as a simple string of of name/value pairs
        $this->bind_arg('values', $jvalues);
        //
        //Convert the json string to a php object of name/value pairs
        $values = json_decode($jvalues);
        //
        //Get the serialized version of this pages sql_edit; its the driver of
       //the parent sql/ parent::
        $sql_edit = $this->get_sql_edit();
        //
        //Create a record based on the sql and the incoming values
        $inrecord = $sql_edit->get_record($values);
        //
        //Save the incoming record's values to the database
        $inrecord->save_data();
        //
        //Retrieve the same saved record using the first identification index 
        //values
        //
        //
        //Formulate the selection condition by following the identification 
        //fields
        //
        //Get the identification indices
        $indices = $inrecord->reftable->indices;
        //
        //Select the first one; in future it will be he only one and it will
        //be defined at the data level (not under reftable)
        foreach($indices as $index){ break;}
        //
        //Start the condition with an empty string
        $condition = "";
        //
        //Step through each index field name and expand the condition string
        foreach($index as $name){
            //
            //Compile the full field name
            //
            //Get the field matching the name. Remember fields is an indexed 
            //array. (Can we not make thos consistent with teh javascript 
            //version where fields is an object?)
            $field = $inrecord->fields[$name];
            //
            //Try to retrieve the field value, formatted for particpating 
            //in an sql
            $value = "";
            //
            //If an index value is missing, throw an exception
            if (!$field->try_writable_value($inrecord->values, $value)){
                //
                throw new Exception("The value of an identifcation field $field->name cannot be empty");
            }
            
            //
            //Formulate the full field name expression
            $fname = "`$inrecord->tname`.`$field->name`";
            //
            //Get the condition separator
            $sep = $condition==="" ? "" : " AND ";
            //
            //Expand the condition
            $condition = "$condition$sep$fname=$value";
        }
        //
        //Get another empty copy of record from edit sql
        $outrecord = $sql_edit->get_record();
        //
        //Fill it with values from the database using the derived condition
        $outrecord->fill($condition);
        //
        //Return the reccord's values
        return $outrecord->values;
   }
    
   
    
    //Construct a new empty record to add to the list of the ones showing on 
    //this page. The new record is laid out in the prescribed format; it will be
    //prefilled with parent record primary key data if this method was called
    //by a descendant
    function add_record($layout_type=null){
        //
        //Bind the layout type, updating this class's property; the get_layout
        //method used later in ths function relies on this fact. Note that this 
        //method is called by an object whose layout we would like to be the one 
        //specified in the argument. This is ok, even if this page may be part 
        //of a complex record; the two objects (which ones ?) do not interfere 
        //with each other. (this is not very clear)
        $this->bind_arg('layout_type', $layout_type);
        //
        //Use this query's parent driver (sql_edit) to derive a new record. Note
        //that the parent drive of page records is an sql_edit; that of the 
        //records page is an sql formulated from the sql_edit
        $driver = $this->sql_edit;
        //
        //Compile values (object) for pre-filing the new record; they come from 
        //a parent record, if any. A page_descendant has a parent; a page_record 
        //has none.
        $values = $this->get_parent_primary_values();
        //
        //Compile the new partially filled in record
        $record = $driver->get_record($values);
        //
        //Get the layout; we rely on the layout_type set above.
        $layout = $this->get_layout();
        //
        //Records are always added in input mode
        $mode = new mode_input();
        //
        //Display the record (thus generating the necessary html code 
        //requested by the client)
        $record->display_data($this, $layout, $mode);
    }
    
    //
    //Returns the primary key values of a parent record. By default, this 
    //page_records object has no parent; so there are no values.
    function get_parent_primary_values(){//page_records
        //
        //Returns an empty list of values object
        return new stdClass();
    }
    
}



//
//A descendant page is a subpage of page_record in the descendants foreign key 
//section. It extends page_table by providing a parent table and its primary
//key field
class page_descendant extends page_table{
    //
    //Properties that extend page_records are:-
    public $parent_tname;
    public $parent_primarykey;
    //
    //Note how the constructor of a descedamt matches the standard practice
    //with the following features:-
    //  The first argument is the query string array, neded by all pages
    //  The next two arguments define a page of record
    //  The last two extends a page of records into a descendant
    //  All aguments after that have null defaults. That allows us to construct
    //  this page with the query string as the only required agument -- a very 
    //  important fact in ajax calls.
    function __construct(querystring $qstring, $parent_tname=null, $parent_primarykey=null){
        //
        $qstring->bind_arg('parent_tname', $parent_tname, $this);
        $qstring->bind_arg('parent_primarykey', $parent_primarykey, $this);
        //
        //Call the parent page table constructor
        parent::__construct($qstring);
        //
        //Override the css expression for a descedant record.
        //
        //The dom page of a decendant page is the dom element whose id matches
        //the table name of this page. This function overrides the default one 
        //which associates a js page with teh artiles or entire document node. A js
        //page is a logical representation of the visual dom page
        $this->cssxp = "descendant[id='$this->tname']";
        //
        //Compile the correct variable for resolving methods menntioned in
        //this page
        $this->jsxp = "page_record.descendants.$this->tname";
        
    }
    
    //Override the get driver method of the parent page_records by limiting the
    //page_records list toprimary key of the parent record.
    function get_driver(){//page_descendant
        //
        //Get the parent driver; retain it but add the foreign key condition
        $driver = $this->get_sql_edit();
        //
        //Formulate the foreign key condition
        $condition = "`$this->tname`.`$this->parent_tname` = $this->parent_primarykey";
        //
        //Condition the driver statement. Note that this is a straight addition
        //of a condition
        $stmt= "$driver->stmt WHERE $condition";
        //
        //Formulate the descendant's driver; it is the same as the original
        //one but the statement has been conditioned. Do not derive a new driver -- 
        //otherwise the descendant will not be a true extension of a page of 
        //record and therefre will mot behave like one
        $driver->stmt = $stmt;
        //
        return $driver;
    }
    
    

    //
    //Returns a list comprising of the only primary key value of this page of
    //a descendant, i.e., [$fname=>$value], where $fname is basic foreign key 
    //field name of this descendant that matches that name of the parent table, 
    //and $value is the value of the parent primary key. For the purpose of
    //prefilling a (hidden) foreign key field, the output and input subfields 
    //are not really necessary
    function get_parent_primary_values(){//page_descendant
        //
        //Get the name of the parent table, i.e, the foreign key field name
        $tname = $this->parent_tname;
        //
        //Get the value of the primary key field
        $primarykey = $this->parent_primarykey;
        //
        //Get the BASIC foreign key field name of this record that matcjes tname
        //
        //Get the foreign key field. This page's driver is sql_edit
        $fkfield = $this->driver->fields[$tname];
        //
        //fkfield is comprises of 3 subfields, viz., primary, output and id. Its
        //the primary we want.
        $subfield = $fkfield->subfields->primary;
        //
        //The required BASIC field name is the name of the subfield. It is the
        //name by which values are indexed in a record
        $fname = $subfield->name; 
        //
        //Compile the requested values stdClass object
        $values = new stdClass();
        $values->$fname = $primarykey;
        //
        //Return the field values
        return $values;
    }
    
    //
    //By default, foreign key columns are shown in all pages. However, in a 
    //descendant, it is hident if its name matches the parent table
    function hide_foreign_keyfield(column_foreign $field){
        //
        return $field->name === $this->parent_tname ? true: false;
    }
    
    //Display a descendant page as part of a record page and return the name
    //of the descendant pluts its structure.
    function show_descendant(){
        //
        //Open a descendant member
        echo "<descendant"; 
        //
        //When clicked on, it becomes the current. Note how we access
        //the active descendant for the indexing tname!!
        echo " onclick='page_record.descendants.$this->tname.select_dom_descendant(this)'";
        //
        //When selected, the member property of the page is set to the 
        //member's table name (saved in the id attribute
        echo " id='$this->tname'";
        echo ">";
        //
        echo "<div class='descendant'>$this->tname</div>";
        //
        //Display the descendant page as a normal list of records that 
        //page_records would output
        $this->display_page();
        //
        //Close a descendant member
        echo "</descendant>";
        //
        //Compile the extra data needed to accompany the htmp generated by this
        //function
        $extra = array("tname"=>$this->tname, "data"=>$this);
        //
        //Returns the extra data
        return $extra;
    }
}

//
//page_mutall enables interaction between the user and other services offer
//my mutall. It extends the ordinar page. How? By ovveriding the default driver
class page_mutall extends page{
    //
    //Structure for reporting the login status
    public $log_status;
    //
    function __construct(querystring $qstring) {
        //
        //Initialiaze the inherited page. The page_records nknow how
        //to extract the data it requires (from the querystrng)to construct 
        //itsel. If that data is not provided, an exception to that effect is 
        //thrown
        parent::__construct($qstring);
        //
        //Set the log sttaus
        $this->log_status = $this->get_log_status();
    }
    
    //
    //Bt design, the login page has no driver; it displays what is provided as it
    //is
    function get_driver(){
        //
        return null;
    }
    
    //Retuens the login status of, i.e., if logged, to which 
    //databse; if  not, invite user to login
    function get_log_status(){
        //
        //Variable for receving the login credentials
        $login = null;
        //
        //Try to get the login credantials
        if ($this->try_login($login)){
            //
            $status = array('is_login'=>true, 'username'=>$login['username']);
        }
        //We are not logged in
        else{
            $status = array('is_login'=>false, 'username'=>"");
        }
        //
        //Return the json encoded status
        return $status;
    }
    
}

//
//This class exytends the page_table by (a) being specific to some record identified
//identified by a primary key and (b) being able to display the descendants
class page_record extends page_table{
    //
    //
    //The primary key field that supplies data to this page
    public $primarykey;
    //
    //Indicates if we should show descendants or not; the default is yes
    public $is_show_descendants=true;
    //
    //Descendant pages of this page. A descendant is a page derived from a 
    //table whose one of teh foreign key fields points to the table name driving
    //this page
    public $descendants;
    //
    //Construct a record page usinng the Mutall client/server interface method
    //This method has the following demonstrated aspects
    //1 The constructor has the arguments that the client OR ANY OTHER CALLER 
    //  must supply. Their default values are null which means that tHe constructor 
    //  can be called without any of the arguments --an important consideration 
    //  for implementing the ajax mechanism that we use for client/server 
    //  communication. It also means that we can create new pages, e.g., 
    //  descendants from within PHP, i.e., without starting from teh client 
    //2 That arguement values are set from the functions seed when they are not 
    //  provided explicitly
    function __construct(querystring $qstring, $primarykey=null) {
        //
        //The default layout of a page record is label; but the user can overide 
        //it.
        $this->layout_type = layout::label;
        //
        //The default mode of  a page record is output; but the user can override 
        //it
        $this->mode_type = mode::output;
        //
        //Override the css expression for this reord. It is the element named 
        //parent
        $this->cssxp = "parent";
        //
        //Set the constructor areguments to their selves, if valid, or to their
        //indexed values in the seed, including this page's properties
        $qstring->bind_arg('primarykey', $primarykey, $this);
        //
        //Initialize the parent page_records
        parent::__construct($qstring);
        //
        //Set the dbase from the sql as it is referenced may more times
        $this->dbase = $this->driver->dbase;
        //
        //Enrich this page with descendants data, so that the initialization 
        //process to be invoked in js can consume this data to continue 
        //building this complex page. This is important only if we will be 
        //displaying descendants. By default, e will.
        if ($this->is_show_descendants){
            $this->set_descendants();
        }
    }
    
    //Rfresh the parent node of this recod page
    function refresh(){
        //
        //Get the driver data property o this page; it is a record
        $record_= $this->driver;
        //
        //Activate it, if necessary
        $record = $this->activate($record_);
        //
        
    }
    
    //This function overrides the mutall  activation (which does not recognize
    //page record). It is an important step of extending the base page class 
    function activate_class($classname, $obj) {
        //
        //This is the bit that extemds actovation
        if ($classname === get_class()){
            //
            //Ensure that the constructor data are available, thrwoing an 
            //exception if not
            $tname = $this->get_property("tname");
            $primarykey = $this->get_property("primarykey");
            //
            //Now return the page_class
            return new page_class($tname, $primarykey);
        }
        //
        //Do the activation suggested by the parent class
        return parent::activate_class($classname, $obj);
    }
   
    //Displaying a page_record extends the normal (page_records) display by 
    //incluing the descendants' (markers)
    function display_page(){//page_record
        //
        //Do the parent display under the parent node. This is important when
        //we need to refresh the parent section independent of the descendants
        echo "<parent>";
        parent::display_page();
        echo "</parent>";
        //
        //Display data from tables that are descendants of the reference table
        //Descendants are tables that have foreign key fields that reference the
        //reference_table.
        if ($this->is_show_descendants){
            //
            //Open the descendants section
            echo "<descendants>";
            //
            //To imporive responsivenes only the marker for descendants is 
            //output. The details for each descendant page will be supplied 
            //using the ajax method.
            //
            //Close the descendants
            echo "</descendants>";
        }
        
    }
    
     
    //Delete the selected (single) record
    function delete_record(){
        //
        //Formulate the delete sql
        $sql = "DELETE FROM `$this->tname` WHERE `$this->tname`=$this->primarykey";
        //
        //Retrieve the current dbase
        $dbase = $this->get_dbase();
        //
        //Execute the sql, reporting any error
        if (!$result = $dbase->conn->query($sql)) {
            die($sql . "<br/>" . $dbase->conn->error);
        }
        //
        //Report ok
        $this->ok();
    }
    
    //Get the driver record data of page_record. This function overrides that 
    //of page
    function get_driver(){//page_record
        //
        //Get teh primary key of this record
        $primarykey = $this->primarykey;
        //
        //Use the serialised version of sql_edit for the given table and database 
        //names. It is the driver of the paremt page_table class
        $sql_edit = $this->get_sql_edit();
        //
        //Construct this page's data record, i.e., a new empty (i.e., with no 
        //values) record based on sql_edit.
        $record = $sql_edit->get_record();
        //
        //Formulate the primary key condition
        $condition = "`$this->tname`.`$this->tname`=$primarykey";
        //
        //Fill the record the values of the primary key condition
        $record->fill($condition);
        //
        //Return the filled record
        return $record;
    }
    
     
    //Sets the (empty) descendants of this page_record
    function set_descendants(){
        //
        //Create the descendants node and attach it to this page
        $this->descendants = new stdClass();
        //
        //Construct the sql statement to retrieve all the descendant tables
        //of the current (parent) table.
        //
        //Get the database name of this record; it was set during construction of
        //the record 
        $dbase = $this->dbase;
        //
        //Formulate the statement (using the referential constraints table of the
        //information schema database) for retrieving descendants of the 
        //current (reference) table
        $stmt_desc = "select "
            //   
            //The parent table    
            ." table_name,"
            //
            //The descendant table
            ." referenced_table_name"
            //
            //Use the referential consttains    
            ." from information_schema.`REFERENTIAL_CONSTRAINTS`"
            //
            //Limit to the current datatabase and parent table    
            ." where constraint_schema='$dbase->dbname' and referenced_table_name='$this->tname'";
        //
        //Create an sql object based on the above statement
        $sql_desc = new sql($dbase, $stmt_desc);
        //
        //Execute the query to get mysql::results;
        $results = $sql_desc->execute();
        //
        //Fetch the results and compile the descendants
        while ($result = $results->fetch_assoc()){
            //
            //Get the descendant table name (that is referencing this->tname)
            $tname = $result['table_name'];
            //
            //Create a "hook" for the descendnat page. Deferr the creation of
            //the actual page until a request is made via ajax. This is designed
            //to improve the user responsiveness
            $this->descendants->$tname = null;
        } 
    }
   
}

//
//A page_selector is an extension of page_records that was designed to support
//capturing of data that links the record of some table to its foreign
//key counterpart. It has the following unique behaviour:-
//- It has its own interaction file, page_selector.php, which overrides/extends 
//  that of page_records.
//- It has an output, id, and primary key values (i.e., subfields )
//  associated with the foreign key table
//- It displays only the primary key field of some table
class page_selector extends page_records{
    //
    //Subfields of the primary key with which we called this selector with
    public $id;
    public $output;
    public $primarykey;
    //
    //Note that following the tradition of Mutall, this constructor identifies
    //only those fields that extend this class from its parent, page_records. 
    //That means that page_records would have to figure out how to get its 
    //constructor arguments from query string. This design ensures that the number
    //of the constructor arguments of any class are kept to the bare minimum.
    function __construct(querystring $qstring, $id=null, $output=null, $primarykey=null) {//edit_fkfield
        //
        //Set the field name of the feld where the serialized sql edit is saved
        //the for this page of records. By default it is "serial_edit", but for 
        //a selector query it is "serial_selector".
        $this->serial_driver_fname = "sql_selector";
        //
        //Initialize the inherited page_record class. This is done as early
        //as possible to allow access to the methods of the extended classes. It
        //is safe to do the initialization here since the unbounded arguments
        //above do not contribute to the construction of the class being extended.
        //Note that the (optional) arguments of a constructiong the page_record are
        //ommitted.
        parent::__construct($qstring);
        //
        //Bind the constructor arguments of this class that extends it from the
        //page_records. Note how constructing the parent first simplifies the
        //binding of arguments
        $this->bind_arg('output', $output);
        $this->bind_arg('id', $id);
        $this->bind_arg('primarykey', $primarykey);
        //
        //By default, teh primary key field is never displayed; page_selector 
        //overrides this property
        $this->hide_primary_keyfield=false;
        //
        //Get index of the selector page i.e., the field that is used for 
        //populating the id attribute of the dom record
        $index = field::id;
        //
        $this->index = $index;
        //
        //Set the index value; it is the id field posted with the page
        $this->$index = $id;
    }
    
    //Search for the hinted records and return the result (as a html) to the
    //caller. 
    function search_hint($hint=null){
        //
        //Set the hint argument; also set this page's hint property
        $this->bind_arg('hint', $hint);
        //
        //Retrieve the current sql driver
        $driver1 = $this->driver;
        //
        //Condition the driver statement to the sratch jint
        $stmt2 = "select stmt1.* from ($driver1->stmt) as stmt1 where ".field::output." like '%".$hint."%'";
        //
        //Formulate the sql that will drive this page, based on the 2nd statement 
        //and the fields of the first statement
        $driver = new sql($driver1->dbase, $stmt2, $driver1->fields);
        //
        //Use the query string to return the display style variables, viz., layout and 
        //display mode.
        $layout = $this->get_layout();
        $mode = $this->get_mode();
        //
        //Display the driver sql; the results will share the same style as that of
        //this page
        $driver->display_data($this, $layout, $mode); 
    }
    
}

