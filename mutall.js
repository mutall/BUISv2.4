//A record is the next largest data structure after field and before sql.
//The reference table (which is shared with an edit sql) s required for 
//carrying the table indices needed for saving a record
function record(fields, dbase=null, tname=null, reftable=null, stmt=null, values=null){
    //
    //The fields and values of a record
    this.dbase = dbase;
    this.fields=fields;
    this.tname = tname;
    this.values = values;
    this.reftable = reftable;
    this.stmt = stmt;
    //
    //Until you know how to set the class name automatically...
    this.classname = "record";
    //
    //Call the parent mutall prototype (I dont see much gain in having the parent
    //data object as js does not support abstract methods)
    mutall.call(this);
    //   
    //Copy data from the given dom record on this page this js record
    //(or vice versa) depending on the direction of the desired movement.
    //The destination is either this js record or the given container. The page 
    //argument is important because different layouts tag fields and records 
    //differently. If the optional container argument is supplied, it is 
    //the one that is involved in the data transfer; otherwise it is this 
    //record's values property that serves as the container, i.e., data source 
    //or sink
    this.copy = function (direction, page, dom_record, container=null){
        //
        //The copy process is driven by the fields of this record; retrieve 
        //them.
        var fields = this.fields;
        //
        //If the container is not valid...
        if (container===null){
            //
            //...then create a new object for it... 
            container = new Object();
            //
            //... and let it be the values property of this record.
            this.values=container;
        }
        //
        //Step trough the fields of this record and use them to move the data 
        //in the desired direction
        for(var i in fields){
            //
            //Get the i'th field
            var field = fields[i];
            //
            //From the layout get field element's name, e.g., td or field
            var fEname = page.layout.field_tag_name;
            //
            //Use the element name to formulate a css field selector, e.g.,
            //td[fname='age']
            var fselector = fEname + "[fname='" + field.name + "']";
            //
            //Get the named dom field element from the dom record.
            var dom_field = dom_record.querySelector(fselector);
            //
            //Move the data between the given dom field (on the indicated page) 
            //and the identified source (or sink) in the desired direction.
            field.copy(direction, page, dom_field, container);
        }
    };

    
    //Add the foreign key value to this record. This is important for pre-filling
    //a new descendant record with the data that matches its parent page_record
    this.add_fkvalue = function(page_record=null){
        //
        //This process is valid only if the parent page_record is known
        if (page_record===null){
            return;
        }
        //
        //Prepare to get the primary key field of the foreign key table
        //
        //Get the foreign key table name; it has the same name a the foreign 
        //key table
        var fktname = page_record.tname;
        //
        //Let fkrecord be the foreign key driver record; it has the values we are 
        //interested in.
        var fkrecord = page_record.driver;
        //
        //Now get the primary field of the foreign table
        var primary = fkrecord.fields[fktname];
        //
        //Get the foreign key field of this record; it has the same name as the
        //foreign key table name
        var fkfield = this.fields[fktname];
        //
        //Create the values object of this record; its empty
        this.values = {};
        //
        //Step through th subfields of the primary and copy theier matching values from
        //transferadd the 
        for(var i in primary.subfields){
            //
            //Retrieve the primary key field sql field name
            var primary_fname = primary.subfields[i].fname;
            //
            //retrieve the foreign key sql field name
            var foreign_fname = fkfield.subfields[i].fname;
            //
            //Retrieve the value from the foreign record
            var value = fkrecord.values[primary_fname];
            //
            //Copy the value to this record
            this.values[foreign_fname]=value;
        }
    };
}

//
//A dom recrod extends a php record with a view -- a html element that represents
//the visible part of a record. The $ prefix is added to avoid references to page
//the class constructor which raises confusion
function dom_record(view, $page){
    //
    //Define the empty values of this dom record
    this.values;
    //
    //The visible part part of a record
    this.view = view;
    //
    //We need to access the page to be able to implement page-specifc methods
    this.page=$page;
     //
    //Get the driver (sql) that drives the given page
    var driver = $page.driver;
    //
    //Retrieve the reference table; it is valie only for page_table derivarives
    //(This is currently not done using OO style!!)
    var reftable = typeof $page.sql_edit==="undefined" ? null: $page.sql_edit.reftable;
    //
    //Initialize the parent record (without any values) in order to implement
    //the PHP version a dom record. Note that teh tabel name comes from the
    //page (not the driver)
    record.call(this, driver.fields, driver.dbase, $page.tname, reftable, driver.stmt);
    //
     //Return the values of this dom record from its view. Noe how this method
     //is declared before using it below
    this.get_values = function (){
        //
        //Initialize the values with nothin
        var values = {};
        //
        //The copy process is driven by the fields of this dom record; retrieve 
        //them.
        var fields = this.fields;
        //
        //Step trough the fields of this dom record and use them to move the data 
        //from the vieq to the container
        for(var i in fields){
            //
            //Get the i'th field
            var field = fields[i];
            //
            //From the layout get field element's name, e.g., td or field
            var fEname = this.page.layout.field_tag_name;
            //
            //Use the element name to formulate a css field selector, e.g.,
            //td[fname='age']
            var fselector = fEname + "[fname='" + field.name + "']";
            //
            //Get the named dom field element from this dom record's view.
            var dom_field_view = this.view.querySelector(fselector);
            //
            //Copy the data from the dom field view to this dom record's values
            field.copy(true, this.page, dom_field_view, values);
        }
        //
        //Return the values
        return values;
    };
    //
    //Retrieve the view values to initialize the this record's values property
    this.values = this.get_values();
    //
    //Switch this dom record to (or cancel the) edit mode. 
    this.switch_record_to_edit = function(to_edit){
        //
        //Retrieve the (js) fields of this record
        var fields = this.fields;
        //
        //Run through each js field and put its corresponding dom version
        //into (or out of -- depending on the boolean to_edit argument) edit mode
        for(var i in fields){
            //
            //Get the i'th js field
            var field = fields[i];
            //
            //Get the name of the field
            var fname = field.name;
            //
            //Define a css selector for the only dom field in this dom record
            //with this field name
            var css = this.page.layout.field_tag_name+"[fname='" + fname +"']";
            //
            //Locate the required dom field using the selector on this record's
            //view
            var df = this.view.querySelector(css);
            //
            //Switch to the requested edit mode
            field.switch_field_to_edit(to_edit,df);
        }
    };
    
    //
    //Delete this dom record from its parent
    this.delete = function(){
        //
        //Get the parent of this dom record's view
        var parent = this.view.parentElement;
        //
        //Remove this view
        parent.removeChild(this.view);
    };
    
    //Returns all the empty blank fields for the given identification fields
    //The check is done before saving the data to a database. It proceeds by:-
    //(1) verifying that no identification field is blank 
    //(2) reporting the field (by highlighting it in red) if there is 
        //one that is blank
    this.get_blank_idfields = function(){
        //
        //Get the identification indices of this record from the reference 
        //table object; 
        var indices = this.reftable.indices;
        //
        //Get the first index name; it is as good as  any other for
        //our current purpose.
        //
        //How do you to get the first property of a any structure? 
        //Object.keys(indices) returns an array of indices. Then you access 
        //the first one as [0]. For now, use this method; it works
        var fnames ;
        for(var i in indices){
            //
            fnames = indices[i];
            //
            //Get out of the for loop after the first index
            break;
        }
        //
        //Collect the blank fields
        //
        //Start with an empty list of fields
        var fields="";
        //
        //Loop through all the given identification field names and collect the 
        //emptyones 
        for(var i in  fnames)
        {
            //Get the i'th field name
            var fname = fnames[i];
            //
            //Get the js field; we assume that the page's data is an sql. What 
            //if it is not?
            var field = this.fields[fname];
            //
            //Retrieve the name of teh primary subfield of the js field. For a normal 
            //js field this is the same as the field's name. In contrast, that 
            //of a relation field is the id subfield.
            var basic_fname = field.get_fname("primary");
            //
            //Get the named basic field value. 
            var value = this.values[basic_fname];
            //
            //Verify that this value is not empty; otherwise highlight the 
            //empty field and indicate a failure
            if ((value === '') || (typeof value==="undefined")) 
            {
                //
                //Mark (i.e., show) the empty values in red
                //
                //Formulate a css selector for input elemet of the named field 
                var fselector = this.page.layout.field_tag_name + "[fname='"+fname+"']";
                //
                //Get the input element of the named dom field
                var input = this.view.querySelector(fselector);
                //
                //Set the backround as red.
                input.setAttribute("style", "background:red");
                //
                //Add the empty field to some collection, separated by comma
                fields = fields + (fields==="" ? "" :", ") + fname ;
            }
        }
        //
        return fields;
    };
    
    //   
    //Update the dom record, including its view, with the given values. The view
    //impliws its fielsa, as well as the primary key attribute of the dom record.
    //This is important, otherwise the newly added record is not equal to the 
    //older records -- which means that it may not immediately support such 
    //operations as delete, which rely on the primary key of a record
    this.update_view = function (values){
        //
        //Set the values property
        this.values = values;
        //
        //Update the view. The process is driven by the fields of this record; 
        //retrieve them.
        var fields = this.fields;
        //
        //Step trough the fields of this record and use them to move the data 
        //in the desired direction
        for(var i in fields){
            //
            //Get the i'th field
            var field = fields[i];
            //
            //From the layout get field element's name, e.g., td or field
            var fEname = this.page.layout.field_tag_name;
            //
            //Use the element name to formulate a css field selector, e.g.,
            //td[fname='age']
            var fselector = fEname + "[fname='" + field.name + "']";
            //
            //Get the named dom field element from the dom record.
            var dom_field_view = this.view.querySelector(fselector);
            //
            //Copy data from the given values to this dom record's view
            field.copy(false, this.page, dom_field_view, values);
        }
        //
        //Update the primary key attribute of this record's view
        //
        //Get the primary key field name; it has the same as the table of
        //this record
        var pkfname = this.tname;
        //
        //Get the (composite) primary key field from this record's fields
        var field = fields[pkfname];
        //
        //Get the primary key subfield of the composite field; other subfields
        //are indexed as input and id
        var subfield = field.subfields["primary"];
        //
        //Get the basic field name of the primary key; its teh one used for \
        //indexing the data
        var bpkfname = subfield.name;
        //
        //Use the basic field name to retrieve the primary key value from the
        //input values
        var primarykey = values[bpkfname];
        //
        //Set the primary key attribute of this record's view.
        this.view.setAttribute("primarykey", primarykey);
    };
}

//Modelling the general field
function field(name){
    //
    this.fname = name;
    //
    //Initialize the inherited mutall system. (I dont see much gain in having 
    //the parent data object as js does not seem support abstract methods)
    mutall.call(this);
    
    //Editing this ordinary field simply sitches on the edit mode on the given
    //dom field and trabsfers focus to the field. We assume that the input and 
    //output elements are already synchronized. 
    this.edit_field = function(df){//field
        //
        //Switch this field to edit mode
        this.switch_field_to_edit(true, df);
        //
        //Transfer focus to the input element
        var input = df.querySelector("input");
        input.focus();
    };  
    
    //The basic field name of a normal js field is the same as the field
    //name. In contrast, that of a relation field is the indexed subfield
    this.get_fname = function(index){
        return this.name;
    };
    
    //
    //Switch this relation column to (or cancel the) edit mode -- given 
    //its (dom field) view
    this.switch_field_to_edit = function(to_edit, view){
        //
        //Get the output element from the viewd. Remember that every view 
        //element has an output tag; also that not all views have an input tag,  
        //e.g., the primary key field. 
        var output = view.querySelector("output");
        //
        //Get the input element; it may be null (e.g., for the case of primary 
        //key described above)
        var input = view.querySelector("input");
        //
        //Now do the requested switch
        switch(to_edit){
            //
            //Switch to edit mode; that means show the input and hide the output 
            //tag
            case true:
                //
                //Hide the output tag
                output.setAttribute("hidden", true);
                //
                //Show the input tag with the same value as the output
                input.removeAttribute("hidden");
                input.value = output.textContent;
                break;
            //
            //Switch to display mode; that means hide the input tag and show 
            //the output one
            case false:
                //
                //Hide the input tag
                input.setAttribute("hidden", true);
                //
                //Hide the output tag
                output.removeAttribute("hidden");
                //
                //Reset the input value to the output??????
                //input.value = output.textContent;
            break
        }
    };

    //
    //Copy THE INPUT DATA (what about the id and _output child elements? data 
    //from the given dom field to the given values object (or vice versa -- 
    //depending on the direction of the specified movement). 
    //(Why is page needed? Its only valid for record copying where we use it to
    //identify the propery field tag, td ot field -- depending on page layout retrieve)
    this.copy = function(from_dom, page, dom_field,values){
        //Identify the input child element of a normal field
        //
        //The input element allows data editing; it has the more updated data 
        //than the output element -- but not every field has it.
        var input = dom_field.querySelector("input");
        //
        //Every normal field has an output tag
        var output = dom_field.querySelector("output");
        //
        //Get the name of the field
        var fname = this.name;
        //
        //Depending on the direction of the copy, move the data.
        switch(from_dom){
            //
            //Copy the data from dom field to the container. Ths is teh case
            //of a normal field where the output=input tag elemens. Unlike the
            //relation fiels, there is no id field
            case true:
                //Valid only if the input is valid, as it has the latest value
                if (input!==null){
                    values[fname] = input.value;
                } else{
                    //
                    //Valid in all cases
                    values[fname] = output.textContent;
                }
                break;
            //
            //Copy data from the container to the dom field
            case false:
                //
                //Valid only if input is valid; the parimary key has no input
                if (input!==null){
                    input.value = values[fname];
                }
                //
                //Valid in all cases
                output.textContent = values[fname];
                break;
        }
    };
}

//A column is a field that can be saved to a database, so it not only has a name
//i alao has a table name to be associated with
function column(name){
    //
    //The name of this column should as required by a database table. What is 
    //the justification for having 2 names: name and column_name? Historical 
    this.column_name=name;
    //
    //Inherit the field object 
    field.call(this, name);
}

//A primary key field is a special field
function column_primary(name){
    //
    //Fields that are special to a primary key column
    //
    //The friendly field used for represeting a record
    this.criteria; 
    //
    //The id field used for hreferecing purposes
    this.id;
    //
    //The primary key field used for effactng updates
    this.primarykey;
    //
    //
    //Inherit the field properties using  the static source
    column_relation.call(this, name);
    //
    //The primary key need not be attached for editing purposes
    this.attatch=function(edit_window, article){return;};
    
    //
    //The primary key feld cannot be edited
    this.edit_field = function(){//column_primary
        //
        alert("The primary key field cannot be edited");
        return false;
    };
    
    //The primary column is not affected by switching its edut mode to on or off
    this.switch_field_to_edit = function(){};
    
}

//A forein key field is a special field
function column_foreign(name){
    //
    //Fields that are special to a primary key column
    //
    //The friendly field used for represeting a record
    this.output; 
    //
    //The id field used for hreferecing purposes
    this.id;
    //
    //The primary key field used for effactng updates
    this.primarykey;
    
    //Inherit the field properties using teh static source
    column_relation.call(this, name);
    
    //
    //Edit this foreign key field, given its dom field view and page. The view is
    //an element, td or field, depending on the page layout, that represents
    //the visible part of this foreign key record. The page also supplies the
    //needed dbname to inselect records from.
    this.edit_field = function(dom_field_view, page){//edit_fkfield
        //
        //Let old_values be empty variable for receiving the data to be 
        //transfered from the dom field. The page selector to be called later
        //wiill return withe new values that w shat put back to the dom_field_vie
        this.values = {};
        //
        //Copy the data, i.e, primary, id and output values, from the dom field 
        //to the values object, guided by this field.
        this.copy(true, page, dom_field_view, this.values);
        //
        //The table name required for driving the record selectorSet is the 
        //foreign key table associated with this field
        var tname = dom_field_view.querySelector("fk_table_name").textContent;
        //
        //Define the dimension specifications of the login window and place it 
        //relative to the dom field
        var top = 0;
        var left = dom_field_view.offsetLeft;
        var specs = "top="+top+",left="+left+",height=400,width=400";
        //
        //Prepare the query string (requirements) of the selector page
        var qstring = {
            //
            //Parameters for defineing a page of records
            tname:tname,
            dbname:page.dbname,
            //
            //Set the values from the subfields of this foreign key. This is
            //the data that extends a page_records to a page_selector
            output:this.get_value("output"),
            id:this.get_value("id"),
            primarykey:this.get_value("primary")
        };
        //
        ///Request the server to open a new selector page, with the given input
        //wait for user interact with it; on return (when the window is closed)
        //extract the returned values to refresh the dom field version (on this 
        //page) being modified. The values is an object comprising of properties 
        //and values that match the primary, id and output subfields of the 
        //foreign key field. We use field.copy() to effect the transfers. The
        //in_field parammeter is the primary key field (the parent of this 
        //foreing key with values attached to it)
        this.open_window("page_selector", qstring, function(in_field){//edit_fkfield
            //
            //Retrieve the values from the incoming field. Remember that 
            //the new values are indexed by the subfields of the primary key 
            //fields that is teh parent of this foreign key; they need to 
            //re-indexed by the names of the (this) foreign key's subfields....1
            var in_values = in_field.values;
            //
            //Define an empty list of the new values, to be transfferd from ...1
            //and re-indexed by the subfields of this foreign key field.
            var re_values = {};
            //
            //Transfer and re-index the incoming values
            for(var i in in_field.subfields){
                //
                //Get the field name that is the source of the data
                var srcfname = in_field.subfields[i].name;
                //
                //Get the field name that is the destination of the data; it has 
                //the same subfield index as the source
                var destfname = this.subfields[i].fname;
                //
                //Copy teh date from teh sourec to the destination, indexed
                //by teh destination
                re_values[destfname] = in_values[srcfname];
            }
            //
            //Copy the re-indexed values, NOT from the dom_field_view to the 
            //new re-indexed values BUT vive versa. Its the opposite of
            //what happedned earlier. When??
            this.copy(false, page, dom_field_view, re_values);
            //
            //Switch the dom field_view into edit mode
            this.switch_field_to_edit(true, dom_field_view);
        }, specs);
    };
 
    
}

//This class represents columns used for establishing relationships between
//tables. The two examples are primary and foreign key columns
function column_relation(name){
    //
    //Call the parent column system
    column.call(this, name);
    //
    //Copy data from a dom field to the given values structure (or vise versa)
    //depending on the copy direction specified by the from_dom argument. The
    //page arguement is not important for field-to-field data copying
    this.copy = function(from_dom, page, dom_field_view, values){
        //
        //Step through all the subfields of a relation field. The index of the 
        //subfield is one of the following: primary, output or id.
        for(var index in this.subfields){
            //
            //Get the (basic) field name of the i'th subfield
            var fname = this.subfields[index].name;
            //
            //Get from the dom field the child element that matches the index.
            //Now the child is either primary, id or output
            var child = dom_field_view.querySelector(index);
            //
            //Now copy the data from values structure to the dom field texy 
            //content (or vice versa depending desired direction)
            switch(from_dom){
                //
                //Copy data from the dom field to the given data structure. 
                case true:
                    values[fname] = child.textContent;
                    break;
                //
                //Copy data from data structure to the text content of the dom 
                //field.
                case false:
                    //
                    child.textContent = values[fname];
                    break
            }
        }
    };
    
    //The basic field name of a relation js field is the name of the
    //id subfield. In contrast, that of a normal field is the same as the
    //field's name
    this.get_fname = function(index){
        //
        return this.subfields[index].name;
    };
    
    //Retirns the field value of the indexed subfield
    this.get_value = function(index){
        //
        //Get the subfield's name
        var fname = this.get_fname(index);
        //
        //Retrieve the named value from this field's values
        return this.values[fname];
    };
    
}

//The mutall page models an ordinary web page. The index is used to define 
//which attribute of a layout (tabular or label) gets set (on selecting a dom
//record) to the record's "id" attribute value. The "id" (value of the index) 
//is needed to select the record to show when the table is initially loaded
function page(index, id, input_){
    //
    //Initialize the parent mutall system. This is done here so that 
    //properties that match the ones in input_ can be overriden by the 
    //constructor
     mutall.call(this, input_);
     //
    //Activate the driver (data) component, so that we can treat it as a js data object
    //rather than just an ordinary object. Note, if there was a data property
    //in the static input_, it will be overriden at this point.
    this.driver = this.activate(this.driver);
    //
    //Activate the layout component
    this.layout = this.activate(this.layout);
    //
    //Activate the mode component
    this.mode = this.activate(this.mode);
    //
    //A tabular layout is characterised by the following attributes?????????
    //
    //1. An index to the id of the record, i.e., tr, that should be dislayed 
    //on load????? Rationalize these arguments. Are they not parto of input_??
    this.index=index;
    //
    //2. A value of the index if known. If not known set the parameter to 
    //false?????
    this.id=id;
    //
    //Save this page to the global session variable under the mutall id, so that
    //it can be re-opened on coming back
    this.save_to_session = function(){
        //
        //Collect the data for re-starting this session; this is the complete
        //query string that comprises of:-
        //- the class name of the page object
        //- the method, i.e., module to execute
        //- all the data necessary to exceute the method
        //
        //Compile the this page's query strin array into a "standard
        //query string", e.g., name=peter&age=20
        std_str = this.compile_std_querystring(this.querystring);
        //
        //Add this pages filename to complete the query string
        complete_str = this.filename + "? ", std_str;
        //
        //Make the query string to send to the server
        var qstring = {
            //Encode the entire query string that was used to evoke this page
            //Note: the querystring variable was passed on from PHP environment
            //when during this page's construction, e.g., 
            //page_reccords = new page_records(<?php $echo page_records;?>
            querystring:complete_str
        };
        //Set the desired module for saving of a sesion
        //The expected output is json string whose data property is set to the
        //ajax result -- if its type property is not set to "error". The result
        //is an ok string if there was  no error
        this.ajax("save_to_session", qstring, "json", function(result){
            //
            //If ok, report ok...
            if (result.extra ==="ok"){
                alert("ok");
            }
            //
            //...otherwise show the error message
            else{
                this.show_error_msg(result);
            };
        });
    };    
     
    //
    //Save this pages index to the windows local storage
    this.save_index = function(){
        //
        //Get the name of the index
        var name = this.index;
        //
        //Discontinue if the index name is not efined
        if (name==="undefined"){
            return false;
        }
        //
        //Get the index valus
        var value = this[name];
        //
        //Discontinue if the value is not defined
        if (value==="undefined"){
            return false;
        }
        //
        //Check if the value node is available in the windows local storage
        if (typeof window.localStorage.id === "undefined"){
            //
            //It is not available; initialize it with no data
            window.localStorage.id = "{}";
        }
        //
        //Convert the storage into an object
        var obj = JSON.parse(window.localStorage.id);
        //
        //Add the value, using the given name
        obj[name]=value;
        //
        //Re-set the local storage
        window.localStorage.id=JSON.stringify(obj);
    };
    
    
    //A page is initialized on loading; This implies a number of things, e.g.,
    //1) selecting the last record attended to which in turn means:-
        //a) marking the requested dom record;
        //b) going to it, thus making it visible.
        //These operations are jointly described as "hreferencing".
    //2) inserting shared menus  
    this.initialize = function(){
        //
        this.show_selection();
        //
        //Add the home button to the menus on the footer
        //
        //Get the footer secstion
        var footer = window.document.querySelector("footer");
        //
        //Create the menuu
        var menu = window.document.createElement("a");
        //
        //Add it to the footer
        footer.appendChild(menu);
        //
        //Modify the menu
        menu.outerHTML = "<a href='page_mutall.php'>Go Home</a>";
    };
    
    //Show the last record that was selected
    this.show_selection=function(){
        //
        //If this tabular layout does not define id, enforce it and set it to 
        //false. Note: the id is provided during table construction. The argument
        //may be ommited
        if (typeof this.id==="undefined") {this.id = false; }
        //
        //Use this default layout's id if it is valid, otherwise read if off the
        //windows local storage
        if (!this.id){this.id=this.get_id(this.index); }
        //
        //If the id is not valid discontinue the show
        if (!this.id) {return;}
        //
        //Now do the hreferencing.
        //
        //1. Mark the requested row
        //
        //Get the dom record with the given id
        var dom_record  = document.getElementById(this.id);
        //
        //If there is no row that matches the given id, then probably it does 
        //not exist. Perhaps it was deleted. Do not continue
        if (dom_record===null) {return;}
        //
        //Make the requested dom record as the only current
        this.select_dom_record(dom_record);
        //
        //2. Scroll the requested row to view -- thus completing the 
        //hreferencing
        window.location.href="#" + this.id;
    };
    
    //
    //Show the current selections after refreshing this page. This means a 
    //number of things. 1) mark a record as current. 2) mark a field in that 
    //record ascurrent. 3) scroll the record into view
    this.show_current = function(){
      //
      //Get the index value of the current record
      //
      //If the index name is not set, then there is no index value; otherwise 
      //return use it
      if (typeof this.index ==="undefined"){ return;}
      //
      //If the index value is not defined, return; otherwise use it
      if (typeof this[this.index]==="undefned"){ return; }
      //
      var index_value = this[this.index];
      //
      //Search for the dom record with this id attriute
      //
      //Limit the search to the dom page of this js page
      var dom_page = this.get_dom_page();
      //
      //Formulate the css selector for the current record
      var cssr = this.layout.record_tag_name + "[id='" + index_value + "']";
      //
      //Get the dom record
      var dom_record = dom_page.querySelector(cssr);
      //
      //Continue only if the recrod is found
      if (dom_record===null){ return; }
      //
      //Mark this record as current
      dom_record.setAttribute("current", "record");
      //
      //Select the curent field
      //
      //This proceeds only if the current field name is valid
      if (typeof this.current_fname!=="undefined"){
        //
        //Retrieve the current dom field from the dom record
        //
        //Compile te css field selector
        var cssf = this.layout.field_tag_name+"[fname='" + this.current_fname + "']";
        //
        //Get the domfield
        var dom_field = dom_record.querySelector(cssf); 
        //
        //If found, set its current attribute
        if (dom_field!==null){
            dom_field.setAttribute("current", "field");    
        }
      }
      //
      //Scroll the dom record into view, placing the recrod at the bottom of the
      //view area
      dom_record.scrollIntoView(false);
    };
    
    //Mark the given dom field, df, as the only current field. Why are not setting 
    //this page's field to the id of df??
    this.select_dom_field = function(df){
        //  
        //Select all currently marked dom fields
        var dfs  = document.querySelectorAll("[current='field']");
        //
        //Remove the current attribute from them
        for(var i=0; i<dfs.length; i++)
        {
            dfs[i].removeAttribute("current");
        }
        //  
        //Make the given dom field as current
        df.setAttribute("current", "field");
        //
        //Transfer the dom field's name to this javsacript page. The field name 
        //is used for re-highlingting the field after a page refresh.
        this.current_fname = df.getAttribute("fname");
        
    };
    
    // 
    //View the records of the currently selected list of tables or records. 
    //Tables are acessed from page_database and records from any of 
    //page_crud's descendants; this the justification of this view_records 
    //function being implemented at the page level. That means  that is option
    //can be evoked from any page
    this.view_records = function(){
        //
        //Ensure that a table has been selected before opening a page of the 
        //table's records
        this.wait(
            //
            //Show this message as a wizzard to guide the user. Waiting for... 
            "select a table to view its records",
            //
            //This is is called when we need to test if a table name has been 
            //selected
            "tname_is_selected",
            //
            //This function, view the page of the tname's records, is executed on 
            //selecting a table name, 
            //The page of tables has nothing to report after viewing 
            //records; so the "next" action after that is left out
            function(){
                //
                //The best server file to deliver the needed servive is the page_records.
                //Compile its data (seed) requirements
                var qstring = {
                    //
                    //The table name
                    tname: this.tname,
                    //
                    //The underlying database as selected by teh user
                    dbname: this.dbname
                };
                //
                //Request the server to show the records with default styling.
                //Dont bother with the returned results.
                this.open_window("page_records", qstring);
            }
        );
    };


    
    //
    //Set the table name; this function is called by page.view_records
    //to signal the end of a wait to select a table whose records we want
    //to view. By default, no waiting is needed: the table name is
    //already set
    this.tname_is_selected = function(){
      //
      return true;
    };
    
    //
    //Make the given dom descendant as the only current of all descendants and
    //set the decesnatts table neme
    this.select_dom_descendant = function(descendant){
        //
        //Set this page's descendant table name; its the id of the 
        //dom descendant
        this.descendant = descendant.getAttribute('id');
        //
        //Select all dom descendants marked current
        //
        //Formulate the css selector  for a dom descendant
        var dselector = "[current='descendant']";
        //
        //Now do the selection, searching from the entire document
        var descendants  = document.querySelectorAll(dselector);
        //
        //Remove the current attribute from them
        for(var i=0; i<descendants.length; i++)
        {
            descendants[i].removeAttribute("current");
        }
        //  
        //Mark the given descendant as the current one
        descendant.setAttribute("current", "descendant");
    };
    
    //Make the given dom record as only record one marked current and update
    //the page's index (name amd value(
    this.select_dom_record = function(dr){
        //
        //Set this page's property that matches its index to the id value in 
        //dr. This is how Mutall moves data from the dom record to this page --
        //a proces that is important because it is the page data that gets 
        //moved around.
        //For the page_databases, its the index "dbname"; for the page_database 
        //it is "tname"; for page_records its the field::id  
        var id = dr.getAttribute('id');
        //
        //Setting of the id is valid only if it is exists; for new records doed 
        //not.
        if (id!==null){
            this[this.index]= id;
        };
        //  
        //Select all dom records marked current
        //
        //Formulate the appropriate dom record selector
        var rselector = "[current='record']";
        //
        //Select all the records from the entire document. Only one record is 
        //expected to be current -- so we dont need to limit ourselves to this
        //js page's dom page.
        var trs  = document.querySelectorAll(rselector);
        //
        //Remove the current attribute from them
        for(var i=0; i<trs.length; i++)
        {
            trs[i].removeAttribute("current");
        }
        //  
        //Mark the given dom record as current
        dr.setAttribute("current", "record");
    };
    
   //
   //Close this page properly. This means saving requested data to the window
   //and closing it. If no data is provided, this page object is returned to
   //teh caller; otherwise the requested data is returned
   this.close_window = function(data=null){
       //
       //Save this page's index, if valid, to the local wndow storage
       //this.save_index();
       //
       if (data === null){
         //Save this page to the current windows object
         window[this.mutall_id]=this;
       }
       else{
         window[this.mutall_id]=data;  
       }
       //
       window.close();
   };
   
    //Wait for the given test to be successful, then execute next task.
    this.wait = function(
            //
            //The message to show as a wizzard guide in the error element
            msg,
            //
            //A boolean returning function for testing when we should stop
            //waiting, i.e., clear the timer. It has the signature 
            //boolean test()
            test,
            //
            //The function to execute on the above test being true; its signature is
            //void next()
            next
            ){
        //
        //Test if the waiting is necessary or not
        if (this[test]()){
            //
            //The waiting is not necessary; execute the next task
            next.call(this); 
        }
        //
        //The waiting is necessary, i.e, the condition for executing the
        //next() task is not yet met. Start the wait
        else{
            //
            //Update the error message element to show what we are waiting for
            this.show_error_msg("Waiting for you to " + msg + "....");
            //
            //Freeze "this", to avoid confusion of window.this and page.this This 
            //issu is also present in annymous functions in php.
            var this_ = this;
            //
            //Set the timer interval to 100 milliseconds and test for completion
            var timer = setInterval(function(){
                //
                //Test if we should execute the next function or not
                if (test.call(this_)){
                    //
                    //Yes we should. Stop waiting, i.e., clear the timer.
                    clearInterval(timer);
                    //
                    //Clear the wizzard error
                    this_.clear_error_msg();
                    //
                    //Execute the next requested task
                    next.call(this_);
                }
            }, 100);
        }
    };
    
    
    //Returns the dom element that is visually associated with this js page.
    //It is used to:- 
    //(a) confine a query search, partcularly in compound pages.
    //(b) determine where results of an ajax operation should be "written" to
        //refresh a complex page
    // This function establishes the link between the js and dom pages. 
    //By default, it is the articles node of a page -- if it is found. 
    //For special pages, e.g., the descendant, it is the node with the following 
    //css descendant[id=$tname]
    this.get_dom_page = function(){
        //
        //Get the page css expression for this page
        var cssxp = this.cssxp;
        //
        //Use the css expression to locate the page from the entire document
        var dom_page = window.document.querySelector(cssxp);
        //
        //Its  an error if the page is not found
        if (dom_page ===null){
            alert("No dom page found for css page '" + cssxp+"'");    
            return false;
        }
        return dom_page;
    };
    
    
     //
    //Edit a dom field on this page. If the input element is specified, then 
    //we must have launched this function from an input button, for instance, in
    //editing a foreign key field. In that case the relevant field is the parent 
    //to the input; otherwise the search was launched from the input menu in 
    //which case the relevant field is the "current" one
    this.edit_field = function(input=null){//page
        //
        //Lef df be the dom field to edit
        var df;
        // 
        //If we called this function from the some input element, then the dom field
        //should be the parent of the input element
        if (input!==null){
            //
            //Set the dom field to the input's parent node; otherwise search 
            //for it
            df = input.parentNode;
        }
        //
        //..Otherwise it is the current dom field. There has to be one.
        else{
            //Get the "current" dom field
            df = this.get_current_dom_field();
        }
        //
        //Get the field name; it is an attribute of the dom field
        var fname = df.getAttribute("fname");
        //
        //Retrieve the js field correspnding to the dom field's name. We
        //use the fact that the data property of this page should be an 
        //Sql -- which has js fields. Retrieve the named one
        var field = this.driver.fields[fname];
        //
        //Edit the field, passing both the dom field and this page as parameters. 
        //Why is the page needed? Answer: to supply extra data (e.g., login 
        //credentials) that is required for supporting editing operations in 
        //foreign key fields
        field.edit_field(df, this);
    };
    
    //Returns the current dom field of this page based on the "current" 
    //attribute. An alert is provided if there is no current selection
    this.get_current_dom_field = function (){
        //
        //Try to get the current dom field
        var df = this.try_current_dom_field();
        //
        if (!df){
            alert ("There is no current field selection");
            return false;
        }
        //
        //Return the dom field
        return df;
    };
    
    //Returns the current dom field of this page based on the "current=field" 
    //attribute. No alert is provided if there is no current selection.
    this.try_current_dom_field = function (){
        //
        //Formulate the css selector current field
        var fselector = "[current='field']";
        //
        //Search in the entire document for the current field
        var df = window.document.querySelector(fselector);
        //
        if (df ===null)
        {
            //Return alse quietly
            return false;
        }
        //
        //Return the dom field
        return df;
    };

}

//The mutall object is the root of all BUIS objects
function mutall(input=null){
    //
    //The constant for accessing the mutall data in the global variables in
    //javascript is imlemented via a global propety, data_id, sinc the keyword
    //Const is not well spported by some browsers, notably IE
    this.mutall_id = "mutall";
    //
    //How do you register the class name (the same way we did in php)? This is 
    //important for mutall classes that are created from Js
    //
    //Offload (without activating)the properties im the input to this object, 
    //if valid. The inheriter of this class will activate the proerties of
    //inputs that it requires
    if (input!==null){
        //
        //Complete the rest of the object
        for(var prop in input){
            //
            //Pass any property to the object -- regardless of its 
            //source
            this[prop] = input[prop];
        }
    };
    //
    //mutallify is the activation of any static structure so that its
    //methods can be accesssed from javascript. Typicaly the static structure will
    //have come from a php class
    this.activate = function (input_){
        //
        //Classify input
        var type = this.classify(input_);
        //
        //Define the active input
        var input;
        // 
        switch(type){
            //
            //mutall objects have a classname; use the class to activate the object
            case "mutall":
                input = this.activate_class(input_.classname, input_);
                //
                //Append the remaining properties of the static input to the 
                //active object
                for(var prop in input_){
                    //
                    //Pass every property of the input to newly created active
                    //object. Remember to activate it unconditionally
                    input[prop] = this.activate(input_[prop]);
                }
                break;
            //
            //For an ordinary object offload all the propepries defined by the 
            case "object":
                //
                input = new Object;
                //
                //Complete the rest of teh object
                for(var prop in input_){
                    //
                    //Pass any property to the object -- regardless of its 
                    //source and remember to activate it
                    input[prop] = this.activate(input_[prop]);
                }       
                break;
            //
            //For the array activate all the components
            case "array":
                //
                //Initialize the collection of active inputs
                var input = [];
                //
                //This is an array. Step through it
                for(var i=0; i<input_.length; i++){
                    //
                    //Let a be the i'th active input
                    var a = this.activate(input_[i]);
                    //
                    input[i] = a;
                }
                break;
            //
            //Any other structure is returned as it is
            default:
                input = input_;
        }
        //
        //Return the active input
        return input;
    } ;
 
    //The ajax method sends requests to the server to perform specific functions
    //baseds on "this" mutall object. It has the following arguments:-
    //(a) the function to execute on the server side, a.k.a., the module 
    //(b) seed: the data to send to the server as a posted query string
    //(cb) the expected data type in the response, i.e., how to interpret the
        //response text
    //(c) function to execute on completing the request
    //(d) the class name from which the php file name to be served is derived; 
    //    if not provided, it is derived from the classname of this mutall object. 
    //    This parameter is particularly important when we need to access a 
    //    class different from this one. For instabce, displaying the page_descendant 
    //    page from page_record
    //
    //This (ajax) method of talking to the server does not expect the user to 
    //interact with the mutall object (unlike the open_window method).The 
    //signature of exec is void exec(result) where result has 3 properties:
    //i: the stataus of the retsurned data, viz, ok or error
    //ii: the html from teh server and
    //iii: any extra data depending on the client's request module
    this.ajax = function(module, seed, expected_output, exec, classname=null){
        //
        //Expand the given seed (data) with following standard ajax parameters
        //
        //The class name of the module to execute is either explicitly given by
        //the user or it is derived from this class. It is paricularly
        //important if it is unrelated to this one. In the case when a desired
        //class is an ancestor of this one, our ajax method will use inheritance
        //to locate the nearest ancector that implements the desired module
        seed.classname = classname===null ? this.classname: classname;
        //
        //Enrich this object with the 2 key ajax parameters
        seed.module = module;
        //
        //All functions called via the ajax method should anounce as such
        seed.method = "ajax";
        //
        //How will the results be interpreted when they come back to the client?
        //as a json string or html?
        seed.expected_output = expected_output;
        //
        //Use teh same style as this page
        seed.layout = this.layout;
        seed.mode = this.mode;
        //
        //Convert the seed to a query string
        var qstring = this.compile_std_querystring(seed);
        //
        //The filename to execute is the new ajax
        filename = "ajax.php";
        //
        //Create a new xml http request object to allow communication with 
        //the server
        var xhttp = new XMLHttpRequest();
        //
        //Freeze this object to allow reference to it with functions whose
        //object is windows
        var this_ = this;
        //
        //The expected data to be returned is is text; this is more flexible
        //than other data types, especially if errors are a possibility
        xhttp.responseType = "text";
        //
        //On return, execute the exec function; it has the following signature
        //(mutall this_, string data)
        xhttp.onreadystatechange = function (){
            //
            //Check if the request is ready with no errors
            if (this.readyState === 4 && this.status === 200){
                //
                //Set the expected output; the default is html
                var xout = typeof expected_output==="undefined"
                           ? "??" 
                           : expected_output;
                //            
                switch (xout){
                    case "json":
                        //
                        //The response text needs to be converted to a json 
                        //object before we can continue the processing
                        this_.handle_json_result(exec, this.responseText);
                        break;
                    //    
                    case "html":
                        //
                        //Execute the requested function with the response text 
                        //without processing the response text.Do away with one of
                        //this_
                        exec.call(this_, this.responseText);
                        break;
                    //    
                    default:
                        alert("Requested ajax output " + xout + " is not known");
                };
            }
        };
        //
        //Use the post method to save the record. Save means transferring the
        //data from teh volatile $_POST global variable to the more stable
        //$_SESSION variable. Posting is needed as (a) it can handle large data
        //and (2) the passwords will not be vsible, i.e, more secure
        xhttp.open("post", filename);
        //
        //Send a request header that tells the post method that we are sending it
        //content of the json string type (not just any string)
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        
        //
        //Send the querystring (sed) to the server
        xhttp.send(qstring);
        
    };
    
    //The ajax method sends requests to the server to execute specific method
    //on desired objects of some class name
    //
    //It has the following arguments:-
    //(1) the method to execute on an object of some given class name
    //    By default the class name is the same as that of this ajax caller, but
    //    it can be overriden using the 5th argument -- the optional classname
    //(2) the querystring is the actual data to be posted to the server and must
    //    be sufficient to (a) create the object of the named class and (b) execute 
    //    the desired method on the object
    //(3) the expected data type of the response, i.e., how to interpret the
    //    response text returned from the server. This parameter is used by the
    //    next argument - exec.
    //(4) exec is the call back method to run when the server is ready with a 
    //    result. It has the signature, exec(result), where result depends on the
    //    expectation specified in argument 3. If the expected result is 
    //    "html", then the result is interpreted as such; if it is "json", then 
    //    it is interpreted as an object with 3 properties, viz., 
    //      i: the status of the retsurned data, either, "ok" or "error"
    //      ii: the html code returned by the server and
    //      iii: any extra data retuned by executing the requested method on the
    //      requested object.
    //(5) as mentioned in (1), this argument is used for overiding the default
    //    the class name of ththe desired object
    this.ajax = function(method, querystring, expected_output, exec, classname=null){
        //
        //Expand the given querystring with following standard ajax parameters
        //
        //The class name of the method to execute is either explicitly given by
        //the user or it is derived from this class. The classname is paricularly
        //important if it is unrelated to this one. In the case when a desired
        //class is an ancestor of this one, our ajax method will use inheritance
        //to locate the nearest ansector that implements the desired module
        querystring.classname = classname===null ? this.classname: classname;
        //
        //Add the method to execute
        querystring.method = method;
        //
        //All functions called via the ajax method should anounce this fact. 
        //Do we still need this data, now  that open_windows calles an actual 
        //file? Perhaps in futue, even teh window calls will be via this function
        //with a re-direction to appropriate file. Lets see.
        querystring.type = "ajax";
        //
        //How will the results be interpreted when they come back to the client?
        //as a json string or html?
        querystring.expected_output = expected_output;
        //
        //Create a new xml http request object to allow communication with 
        //the server
        var xhttp = new XMLHttpRequest();
        //
        //Freeze this object to allow reference to it with functions whose
        //object is windows
        var this_ = this;
        //
        //The expected data to be returned is is text; this is more flexible
        //than other data types, especially if errors are a possibility
        xhttp.responseType = "text";
        //
        //On return, execute the exec function; it has the following signature
        //(mutall this_, string data)
        xhttp.onreadystatechange = function (){
            //
            //Check if the request is ready with no errors
            if (this.readyState === 4 && this.status === 200){
                //
                //Set the expected output; the default is html
                var xout = typeof expected_output==="undefined"
                           ? "??" 
                           : expected_output;
                //            
                switch (xout){
                    case "json":
                        //
                        //The response text needs to be converted to a json 
                        //object before we can continue the processing
                        this_.handle_json_result(exec, this.responseText);
                        break;
                    //    
                    case "html":
                        //
                        //Execute the requested function with the response text 
                        //without processing the response text.Do away with one of
                        //this_
                        exec.call(this_, this.responseText);
                        break;
                    //    
                    default:
                        alert("Requested ajax output " + xout + " is not known");
                };
            }
        };
        //
        //The filename to execute is the entry point for all mutall ajax calls.
        //Mind theh version of teh buis -- set from PHP side
        filename = this.version + "/ajax.php";
        //
        //Use the post method to save the record. Save means transferring the
        //data from teh volatile $_POST global variable to the more stable
        //$_SESSION variable. Posting is needed as (a) it can handle large data
        //and (2) the passwords will not be vsible, i.e, more secure
        xhttp.open("post", filename);
        //
        //Send a request header that tells the post method that we are sending it
        //content of the json string type (not just any string)
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        //
        //Convert the querystring structre to teh standard query string format
        //e.g., name=peter&age=25&location=kiserian
        var qstring = this.compile_std_querystring(querystring);
        //
        //Send the standard querystring text to the server
        xhttp.send(qstring);
    };
    
    
    //Handle the result when we expect it to be a json string
    this.handle_json_result = function(exec, js){
        //
        //See if we can json parse the result in order to separate errors 
        //from a successful case;
        try {
            var result = JSON.parse(js);
        }
        //
        //No we cannot json parse the result. An error must have occured.
        //Report it
        catch(e){
            //
            //Parsing could not be done. The actual json error is not important.
            //Report the json text as an error
            this.show_error_msg(js);
            //
            return false;
        }    
        //
        //Yes we can parse the response; check the status
        switch(result.status){
            //
            //Report the data as the error message
            case "ok":
                //
                //Return teh entire result to the caller.
                exec.call(this, result);break;
            case "error":
                //
                //The error message is in the html property of the result
                this.show_error_msg(result.html);
                break;
            //
            //Unknown status
            default :
                //
                //The error message is in the html property of the result
                this.show_error_msg("The status " + result.status + " is not known");
        }

    };
    
 
    //Classify the input as either mutall, object, array or other
    this.classify = function(input){
        //
        //Let t be the type of input
        var t = typeof input;
        //
        //Test whether t is an object
        if (t!=="object"){
            //t is not an object. It must be other
            return "other";
        } 
        //
        //If the input is null return other
        if (input===null){
            return "other";
        }
        //
        //This is an object; Test if it is an array or not
        if( Object.prototype.toString.call(t) === '[object Array]' ) {
            //
            //It san array
            return "array";
        }
        //
        //This is an ordinary object. Test it if it is a mutall objct
        //Get the classname
        if (typeof input.classname==="undefined"){
            //
            //This is an ordinary object
            return "object"; 
        }
        //
        //This is a mutall object.
        return "mutall";
    };

    //Activate the given classname using the given static input
    this.activate_class=function (classname, input){
        //
        //Let a be the active class to be returned
        var a;
        //
        //Consider only class names whose active forms are used in js. Those
        //that are thought not to be useful are not registered
        switch (classname) {
            //
            case "column":
                a = new column(input);
                break;

            case "column_primary":
                a = new column_primary(input);
                break;

            case "column_foreign":
                a = new column_foreign(input);
                break;
            //
            //Activate the record
            case "record":
                //
                //Get the record's (static) fields and activate them
                var fields = this.activate(input.fields);
                //
                //Create the record; the table name  is optional. If input 
                //defines it, the offloading process ensures that it will be 
                //part of the record.
                a = new record(fields, input.dbase,  input.tname, input.reftable, input.stmt, input.values);
                break;
            //
            //Activate the field
            case "field":
                var name = input.name;
                a = new field(name);
                break;
            //    
            case "tabular":
                a = new tabular();
                break;
            //    
            case "label":
                a = new label();
                break;
           //     
            case "mode_input":
                a = new mode_input();
                break;
            //    
            case "mode_output":
                a = new mode_output();
                break;
            //
            //No other field type is expected in this version
            default:
                //Log the message
                //console.log("Class " +classname + " is not registered");
                a = input;
        }
        //
        //Return the active class
        return a;
    };
    
    //Returns the standard query string compiled from the the given query 
    //string object which is presented as a set of name/value pairs
    this.compile_std_querystring = function(obj){
      //
      //Collect the url parts, starting with noting
      var parts=[];
      //
      //For each property of the object concatentate it with its value
      for(var prop  in obj){
          //
          //Encode the value and its property for safety sake
          var part = encodeURIComponent(prop) + '=' +  encodeURIComponent(obj[prop]);
          //
          //Save the part
          parts.push(part);  
      }
      //
      //Return the joined parts -- upersand concatenated
      return parts.join('&');
    };
    
    //gravitate moves the row that contains the given checkbox input to the
    //top of ths list just after the header
    this.gravitate = function(input)
    {
        //If we have just unchecked an row, return immediately
        if (input.checked===false) {return;}
        //
        //Retrieve the tr in which the input is found. Its 2 parents up 
        //because of the interevening td
        var tr = input.parentNode.parentNode;
        //
        //Get the parent of tr; its the tbody I suppose
        var tbody = tr.parentElement;
        //
        //Remove this row's tr from tbody
        tbody.removeChild(tr);
        //
        //Add this rows tr to the top of tbody just after the heading
        tbody.insertBefore(tr, tbody.childNodes[1]);
    };
    
    //
    //Retrieve the row id from the local storage
    this.get_id = function(index){
        //
        //If the index is not set, return a false
        if (typeof index==="undefined") {return false;}
        //
        //Check if the id node is available in the windows local storage
        if (typeof window.localStorage.id === "undefined"){return false;}
        //
        //Convert the storage into an object
        var obj = JSON.parse(window.localStorage.id);
        //
        //Test if there is any entry by under the index of interest
        if (typeof obj[index]==="undefined") {return false;}
        //
        //Return the id (of the index)
        return obj[index];
    };
    
    
       
   //The open window method extends the jasvscript window.open() method with
    //an ability to return the mutall page that was used to drive the opened
    //window. The windows open method has the signature:-
    //
    //win open(url, ...)
    //
    //where the returned win is the newly opened window and the url is a query 
    //string made of the filename to serve plus the its requirent. In contrast, 
    //the mutall version has the following signature:-
    //
    //win open_window($classname, requirement, onfinish, ...)
    //
    //You note that rather than pass the file name, we pass a classname to the 
    //function; the file name is derievd simply by adding the php extension. The
    //main difference is the extra parameter, onfinish. It is a function which is 
    //executed when win is exited properly; that means saving the underyling 
    //page object before the window is closed. It has the following signature:-
    //
    //onfinish(classname)
    //
    //where classname is the object named $classname that
    //was used to drive the display for win. This allows us to interrogate it 
    //for data that we require to update the parent window.
    //
    //A new page can be initiated from any mutall object. This mechanism allows 
    //us to move from one page to another; becuse it relies on the javascript's 
    //window.open method, it can pass to the server ony a limited amount of 
    //data, so the earlier practice of sending a whole page was stopped. That 
    //was the down side, because it meant that we have to be careful 
    //to pass only the critical amount of data to the sever -- the requirement. 
    //The positive aspect is that the method allows the called page to evoke 
    //the onload method which we relied on to build a complex page.
    //
    //This method of talking to the server assumes that the user needs to 
    //interact with the page. This is in contrast to the ajax method which
    //talks to the server without any user intervention and has no data 
    //limitation because we talk to the server via the post command - rather
    //than get.
    //
    //This version uses a query string to pass data to the server
    this.open_window = function($classname, requirement, onfinish=null, specs=""){
        //
        //Add the PHP extension to the classname to formulate the file name to 
        //request from the server
        
        //
        //The filename to execute is the entry point for all mutall ajax calls.
        //Mind theh version of the buis -- set from PHP side
        var filename = this.version + "/"+$classname +".php";
        //
        //The system window.open command uses the get method.
        //Convert the input data to a json string; how do you tell if the 
        //conversion was successful?
        var qstring = this.compile_std_querystring(requirement);
        //
        //Compile the required url; mutall_id is the name of index in the 
        //PHP global $_GET variable that is used for accessing this data
        var href = filename + "?"+ qstring;
        //
        //Now use the window open method to finish the job. Use the _blank 
        //window name to force a new window, so that win and window are 
        //different
        var win = window.open(href, "_blank", specs);
        //
        //Set a timer so that we can check periodically when the new window 
        //is closed by the user
        //
        //Freeze "this" object in order to reference it within the anonymous 
        //function below
        var this_ = this;
        //
        //Wait for the window to be closed, checking after 100 milliseconds
        var timer = setInterval(function(){
            //
            //If the opened window is closed, clear the timer first, then 
            //check to see if the closing was "proper" or not
            if (win.closed)
            {
                //
                //Clear the timer
                clearInterval(timer);
                //
                //Check if the window was propely closed, i.e., if it was closed
                //via the mutall close window menu or via the general window close
                //option. We assume that when "win" was properly closed, the 
                //property "this_.mutall_id" of window "win" is set to some
                //mutall object of class $classname that was used to drive the
                //opened window
                var classname = win[this_.mutall_id];
                //
                //Use the page with the onfinish call back function if it is 
                //valid to do so.
                if (onfinish!==null && typeof classname !=="undefined"){
                    //
                    //Execute the onfinish function. This function ensures 
                    //that this page is updated, depending on who opened
                    //the window 
                    onfinish.call(this_, classname);
                }
            }
        }, 100);
        //
        //Return the opened window -- just in case the caller wants to interogate 
        //it for furtjer details.
        return win;
    };
    
    //
    //Set this mutall object with the login credentials of the given page. An
    //error will be thrown if such credentuals are missing
    this.set_login = function(page){
        this.username = page.username;
        this.password = page.password;
        this.dbname = page.dbname;
    };
  
    //Show the error message on this mutall page
    this.show_error_msg = function(msg){
      //
      //Get the error element on the page
      var error = document.getElementById("error");
      //
      //It is an error if you dont have the error element defined on this page
      //Show both the error and the need for an error element on this page
      if (error===null){
          //
          alert(msg + "\nNo element found with id='error' on page " + this.classname); 
      }
      else{
        error.innerHTML=msg + "<br/>(Click to clear this error)";    
      }
    };
    
    //
    //Clear the error message
    this.clear_error_msg = function(){
      //
      //Get the error element on the page
      var error = document.getElementById("error");
      //
      //It is an error if you dont have the error element defined on this page
      //Show both the error and the need for an error element on this page
      if (error===null){
          //
          alert("Element 'error' is not defined on page " + this.classname); 
      }
      else{
        error.innerHTML="";    
      }
    };
    
 }
 
 //The label layout of data supports interaction with data that is laid out in
//the label format.
function label(input=null){
    //
    //Set the classname manually. (Infuture this shouls be automated)
    this.classname="label";
    //
    //The label inherits from the layout object.
    layout.call(this, input);
}

//The tabular layout
function tabular(input=null){
    //
    //Set the classname manually. (Infuture this shouls be automated)
    this.classname="tabular";
    //
    //The tabular layout inherits from the layout object.
    layout.call(this, input);
}

//
//The input mode for displaying the data. (This is a dummy class)
function mode_input(input=null){
    this.classname = "mode_input";
}

//The output mode of displaying data. This is a dummy class)
function mode_output(input=null){
    this.classname = "mode_output";
}


//layout is inherted by all pages -- both labels and tables. The layout is 
//designed to be initialized from a json string derived in a php environment, so
//all the necessary initialization data is expected to be held in the static 
//input_.
function layout(input_=null){
    //
    //A layout must be associated with field and record tag names
    this.field_tag_name;
    this.record_tag_name;
    this.body_tag_name;
    //
    //Initialize the parent mutall object; the properties in input will be 
    //added to this object.
    mutall.call(this, input_);
    
}
 