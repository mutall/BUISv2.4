//Buis.js is a libary of javascript "classes", function constructors to be precise,
//that extend the mutall.js libray. The latter is a core library used for 
//building Mutall data services. See the Buis.php for 
//further details.
//
//
////The records page is used for representing (and interacting with) multiple
//records of (sql) data. It is placed here as separate file, rather than being 
//part of page_records.php as is the normal case, because this page is 
//referenced in another page -- page_descendant. A descendant inherits from this 
//records page
function page_records(input_){
    //
    //The id of the record to select on load 
    this.id;
    //
    //The following records layout properties are set during user interaction
    //
    //Current criteria is used for populating the articles section
    this.criteria;
    //
    //The ordering of the selected items
    this.order_by;
    //
    //Primary key and focus field name of the current selected row
    this.primarykey;
    this.focus_name;
    //
    //Set the db and table names from the input
    this.dbname=input_.dbname;
    this.tname = input_.tname;
    //
    //Let the index of this page simply be the table name. In future we will 
    //need to formulate a more defining index, comprising of e.g., the database
    //name plus the table name 
    var index_name =  input_.tname; 
    //
    //Initialize the parent page using the above an initial hreferencing
    //index (name), no index value, and the static data supplied by the
    //server. hy cannot all teh constructor data for page be in the input_?
    page.call(this, index_name, false, input_);
    
    //Save the current record to the database and refresh the dom version.
    this.save_current_record = function(){
        //
        //Get the current dom record from this page; it has the values we need. 
        //An alert will be raised if none is found.
        var dom_record = this.get_current_dom_record();
        //
        //Save the record, but first disallow empty identification fields
        //
        //Handle the identification data, i.e., collect it, check for missing
        //values (reporting if any). Abort this process if any of the 
        //identification fields is empty. The index valiadation process will 
        //have highlighted the ones that are empty and an appropriate error 
        //message dislayed on this page's error node.
        //
        //Get the empty identification fields
        var fields = dom_record.get_blank_idfields();
        //
        //Abort this process at least one empty identification field is found
        if (fields!==""){
            //
            //Compilelist of empty fields message
            var msg = "The following identification fields are blank: "+fields;
            //
            //Display it
            this.show_error_msg(msg);
            //
            //Exit this function; hopefully the get-index-value process will
            //have displayed teh empty data error
            return false;
        }
        //
        //Get the querystring that is appropriate for saving this page. A 
        //descendant page needs more than a age of records
        var qstring = this.get_querystring(dom_record);
        //
        //Save the current record and return, as extra data, the json string
        //of the saved values, as a name/value pairs object 
        this.ajax("save_current_record", qstring, "json", function(result){
            //
            //The result's extra data is an object of name/value pairs
            var new_values = result.extra;
            //
            //Set the new values to the dom record -- this is a form of a very 
            //controlled refresh
            dom_record.update_view(new_values);
            //
            //Swith to display mode (i.e, not edit mode)
            dom_record.switch_record_to_edit(false);
        });
    };
    
    //Returns a query string object that is fit for supporting CRUD operations 
    //on this page. If dom_record is missing, then the values property
    //is an empty object
    this.get_querystring = function(dom_record=null){//page_records
        //
        //Initialize the values from the dom record
        var values = dom_record===null ? {} : dom_record.values;
        //
        //Save the record's data to the server using the ajax method
        var qstring = {
            dbname:this.dbname,
            tname:this.tname,
            //
            //Remember to json encode the name/value pairs
            values:JSON.stringify(values),
            //
            //Why do we need to display the record body without the header?
            body_only:true,
            //
            //The extra parameters
            layout_type:this.layout_type
        };
        //
        return qstring;
    };    
   
    //Extend the querystring with the arguments of a this page's constructor;
    //that depends on the caller. For instance, page_record extends the query 
    //string using the primary key value.
    //  
    //Consider re-doing this so that every object knows how to extend the query
    //string. If this propagates through the object hierarchy, we can greatly
    //simplify the construction of a querystring
    this.extend_querystring = function(qstring){
        //
        //A page records need no extension
    };
   
    //Returns the current dom record of this page based on the "current" 
    //attribute. If there is no current this function alerts the user, then 
    //fails.
    this.get_current_dom_record = function (){
        //
        //Try to get a current dom record
        var dom_record = this.try_current_dom_record();
        //
        if(dom_record){
           return dom_record; 
        } 
        else{
            alert("No dom record is selected");
            return false;
        }
    };
    
    //Returns the current dom record of this page based on the "current" 
    //attribute. If there is no current this function fails quietly. 
    this.try_current_dom_record = function (){
        //
        //Formulate the current record css selector. Note that the curent 
        //selector is designed to be independent of the records layout
        var rselector = "[current='record']";
        //
        //Retrieve the current dom record element by searching in the entire 
        //document to represent the viewable part of a record
        var view = window.document.querySelector(rselector);
        //
        //Test if the search returned a valid element
        if (view===null)
        {
            //Return false (quietly)
            return false;
        }
        //
        //Create a dom record that links the visible part of of a record, view, 
        //and the PHP representation of the same based on this page.
        //
        //Note the adopted PHP variable naming style to avoid confucion between
        //the window-level variables, $dom_record the variable and dom_record the
        //class function
        var $dom_record = new dom_record(view, this);
        //
        //Return the dom record
        return $dom_record;
    };
   
        
    //Add a new record to this page. The strategy is to instruct the server to 
    //construct a record then append it to the 
    //beginning of the current table, just after the header, i.e., as the first
    //child of node tbody. This is designed to work for both types of record 
    //layout, viz., tabular and label. If the pre-filling of a table depends on
    //the page class from which this function was called. The default behaviour
    //is none
    this.add_record=function (){
        //
        //Get teh query string fit for supporting CRUD opertaions on records 
        //based on this page. The dom_record component is missng for ned records
        var qstring = this.get_querystring();
        //
        //The expected output from adding a record is the html to be appended 
        //to this page's records just after the table's  heading. Note that we
        //have overriden the default class to be passed to the server with the
        //appropriate one, this.class, ratjer than hanrd wire "page_records". 
        //That ensures that the correct class will be requested to execute this
        //function by teh server in PHP.
        this.ajax("add_record", qstring, "html", function(result){
            //
            //Get the tbody element as we will need to access her children. 
            //There is no assumption that the current layout is a table, only 
            //that the tbody element is available, even when there are 
            //no records. Note how we confine the search to the correct dom page;
            //this is important because a descendant page (which inherits this 
            //one) is not associated with the entire document but a section of it
            var tbody = this.get_dom_page().querySelector(this.layout.body_tag_name);
            //
            //Retrieve the header record; its the first child of tbody
            var header = tbody.firstElementChild;
            //
            //Let dview be a a dummy dom record. (CreateElement is a property of
            //document note -- not just any element)
            var dview = window.document.createElement(this.layout.record_tag_name);
            //
            //Insert the dummy tr after the header
            header.after(dview);
            //
            //Replace the dummy view record with our correct version after correct 
            //placement. NB: This does not affect the structure of view, contrary
            //to expectation. Explain this a bit more!! ....1
            dview.outerHTML = result;
            //
            //Return the just inserted dummy view. IT IS DIFFERENT INSTANCE FROM 
            //dview. It is the record view we need; lets just call it view
            var view = header.nextElementSibling;
            //
            //Ensure that the dom record, tr,  is the current one on this page
            this.select_dom_record(view);
            //
            //Transfer focus to the first input of type text of the inserted
            //tr
            var txt = view.querySelector("input[type='text']");
            if (txt!==null){
                txt.focus();
            }
        }, this.classname);
        
    };
    
     //Put the current dom record into edit mode
    this.edit_record = function(){
        //
        //Get the current dom record, alerting the user if none is selected
        var dom_record = this.get_current_dom_record();
        //
        //Switch the dom record to edit mode
        dom_record.switch_record_to_edit(true);
        
    };
    
     //Cancel the record/edit operation by setting the input fields of the 
    //current table row (not to edit mode) and resetting their values to what
    //was they were in the output text 
    this.cancel_edit = function(){
        //
        //Get the current dom record on this page
        var dom_record = this.get_current_dom_record();
        //
        //Switch off the edit mode
        dom_record.switch_record_to_edit(false);
    };
    
    //Use the given criteria to inflience the search
    this.search_criteria = function(){
        //
        //Read the criteria field and update the matching property
        this.criteria = document.getElementById("criteria").value;
        //
        //Read the order-by field and update the matching property
        this.order_by = document.getElementById("order_by").value;
        //
        //Read those fields that are hidden
        this.hidden_fields = document.getElementById("hidden_fields").value;
        //
        //Formulate the query string requirements for evoking the display page 
        //using the search view
        var qstring = {
            //
            //Set arguments of page_records constructor; they are important for
            //retrieving teh serialized sql_edit
            tname:this.tname,
            dbname:this.dbname,
            //
            //The arguments of this method in the search view.
            //
            //Set the criteria and order by clauses for the search.
            criteria: this.criteria,
            order_by: this.order_by,
            hidden_fields: this.hidden_fields,
            //
            //Start display from the first record
            offset:0,
            //
            //Show the headers in the initial search
            body_only:false,
            //
            //Show as many records as are needed for the scroll bars to appear; 
            //otherwise they wont. Too high a number is also bad, because that 
            //would reduce the responsiveness when the search results are 
            //initially reported.
            limit:this.full_limit,
            //
            //Ensures that the returned results have the same style as this page
            layout_type: this.layout.type,
            mode_type:this.mode.type
        };
        //
        //Populate the dom node that corresponds to this page with html 
        //resulting from executing the search on the server (using the ajax method)
        this.refresh("display_page", qstring);
    };
       
    //Refresh this page by re-displaying it at this page's $node using the given
    //querystring. If teh metgod is not epecified, we assume its the display page.
    //The query string is specified to reflect the desired page
    //view; if not,then it is built from the current this page properties. This
    //typically happens when the user requests for a refrech
    this.refresh = function(method="display_page", qstring=null){//use_style
        //
        //If the method is not 
        //Inititialize the query string with the current page properties so that
        //the page display view is exactly like the current one.
        if (qstring===null){
            //
            //Use the this page properties to build the query string
            qstring = {
                //
                //Set the constructor arguments of page record
                tname:this.tname,
                dbname:this.dbname,
                //
                //The arguements of this display page method in the search view.
                //
                //Set the criteria value to search for from the current criteria value.
                criteria: this.criteria,
                //
                //Set the offst to the current one ofor thios page
                offset:this.offset,
                //
                //Set the scrool page size
                limit:this.limit,
                //
                //Set teh body headers
                body_only:this.body_only,
                //
                //Set the page styling
                layout_type:this.layout_type,
                mode_type:this.mode_type
            };
        }
        //
        //Use the query string to request for the html of the specified
        //page display and use it to rebuild the node for this page
        this.ajax(method, qstring, "html", function(html){//refresh, search_criteria
            //
            //Get the dom page $node to write the results to.
            var page = this.get_dom_page();
            //
            //Write the html data to the page. Use the innerHTML, so you must 
            //make sure that the returned page does not have the node, e.g.,
            //article for this page. This ensures that attributes we have 
            //specified for article in the page's html will not be overwritten.
            page.innerHTML = html;
        });
        
    };         
   
    //Use the given style, i.e., layout and mode, to re-display the dom records 
    //of this page
    this.use_style = function(style, value){
        //
        //Set the style properties of this page
        switch(style){
            //    
            //Set the layout property of this page
            case "layout":
                //
                //Set the pages's layout type
                this.layout_type = value; 
                //
                //Refresh this page's layput object
                switch(value){
                    case "tabular": this.layout = new tabular(); break;
                    case "label": this.layout = new label();break;
                    default: alert("layout "+value+" is not known");
                }
                break;
            //  
            //Set the display mode propertoes of this page
            case "mode":
                // 
                //Set the basic layput mode type for this page
                this.mode_type = value;
                //
                //Refresh this page's mode property
                switch(value){
                    case "mode_output": this.mode = new mode_output();break;
                    case "mode_input": this.mode = new mode_input(); break;
                    default: alert("mode "+value+" is not known");
                }
                break;
            //
            //The style is not known error
            default: alert("Style "+ style + " is not known");
        };
        //
        //Refresh, so that the new settings can take effect
        this.refresh();
    };
    
    
    //View the current record's data plus that of all her dependants. The
    //selected record may be in page_records or page_descendant. page_descendant
    //inherits from page_records. That is the justfication for the view record
    //to be defined at the page_records level.
    //A record is an exampele of a complex page; it has more than one logical
    //page in one physical page. Put in another way, it is a single (page) file 
    //that displays data from more than one mutall page. The pages are styled
    //independetly
    this.view_record = function(){//page_records
        //
        //Get the current dom record. If none is selected, no alert will be 
        //given and the function will return a false quietly.
        var dr = this.try_current_dom_record();
        //
        //Check the returned dom field; there has to be one!
        if (!dr) {
           alert("No valid record is found. Please select one");
           //
           //Discontinue processing
           return false;
        }
        //
        //Prepare to open a new page_record starting normally with the parent
        //page and proceding to open the descendants via multiple requests to
        //the server via jax -- another justification for the complex page label.
        //These requests are starterd by the page.initialize() function
        //which can only be called by opening the the page window.
        //
        //Collect the minimum data required to open a recod
        var qstring = {
            //
            //Set the dbase (login credentials)
            dbname: this.dbname,
            //
            //Set the parent table name -- the one that drives the page
            tname: this.tname,
            //
            //Set the primary key field from the dom record; this helps to formulate
            //the sql condition for retrieving the field values.
            primarykey: dr.view.getAttribute('primarykey')
            
        };
        //
        //Debug
        //console.log(qstring);
        //
        //Now open the record as a full window (hence no specs). This is a 
        //complex page, so start small and be ready to build other page 
        //components via ajax. Do nothing on terminating interactions with the 
        //page_record; in future we would update current record of caller 
        //page_records (just in case there were changes that need to take effect).
        this.open_window("page_record", qstring);//view_record
    }; 
    
    //Define the function for infinite vertical scrolling on the given element 
    //in page_records
    this.vscroll = function(element){
        //
        //Set the condition for being at the botton of the page. 
        //
        //Let $t be the total, i.e, entire, scrollable height of the given 
        //element
        var $t = element.scrollHeight;
        //
        //Let $c be the current top, i.e, y, position of the scroll button
        //relative to $t
        var $c = element.scrollTop;
        //
        //Let $h be the height of the actual visible window part that represents
        //the element.
        var $h = element.clientHeight;
        //
        //When at the botton, the total scroll height of the element is the same as 
        //its current scroll position plus its ownn height
        var at_bottom = $t === ($c + $h);
        //
        //Do not scroll when there is no more data; this is not necessary as the 
        //scroll event will not be fired if there is no more data to scroll
        //var more_data = true;
        //
        //Check if we need to fetch more records for the page; we do when we 
        //are are at the bottom of the scrollable range
        if (!at_bottom){
            //
            return;
        } 
        //We do need to verticall scroll
        //
        //Update this page's data offset; it is the earlier value plus the 
        //LAST page size
        this.offset = this.offset + this.limit; 
        //
        //Formulate the query string for evoking the display page using the
        //search display view
        var qstring = {
            //
            //Set the arguments of the page_records constructor; they are 
            //important for retriving the serialized sql_edit
            tname:this.tname,
            dbname:this.dbname,
            //
            //Set the arguments of the disply page method in the search view
            //
            //If there is no criteria, assume it is empty
            criteria: this.criteria,
            //
            //If there is no order_by , assume it is empty
            order_by:this.order_by,
            //
            //Set the offset to the updated one
            offset:this.offset,
            //
            //The returned html should not be headed
            body_only:true,
            //
            //The scroll page limit depends on the extended page_records. For
            //instance, in the case of a page_selector, the page limit should 
            //be smaller than that of the parent page_records.
            limit:this.limit,  
            //
            //Ensure that the returned result has the same style as this 
            //page. Note how we ensure that a simple value is passed to the 
            //server, i.e., we ara passing layout_type, rather than layout.
            layout_type: this.layout.type,
            mode_type:this.mode.type
        };
        //
        //Fetch the next page and append the resulting headerless records to be 
        //children of the tbody node
        this.ajax("display_page", qstring, "html", function(html){//vscroll
            //
            //Update the page only if html is not empty
            if (html!=="" || html!==null){
                //
                //Get the dom page to write to:
                var page = this.get_dom_page();
                //
                //Retrieve the table body
                var tbody = page.querySelector("tbody");
                //
                //Create a dummy dom element assuming that the layout is 
                //tabular. You can assume anything for the dummy. The outerHTML
                //command that follows will replace it with the correct tag
                var dummy = window.document.createElement("tr");
                //
                //Attach it to the page
                tbody.appendChild(dummy);
                //
                //Change the outer html of the dumy to the incoming data
                dummy.outerHTML = html;
            }
        });
    };
           
    //Delete the selected record from the database, then and refresh the page
    this.delete_record=function(){
        //
        //Get get the current dom record of this page
        var dom_record = this.get_current_dom_record();
        //
        //Skip this process if the row is not valid
        if (!dom_record) {return;}
        //
        //Confirm the delete and continue if necessary.
        var yes = window.confirm("Do you really want to delete this row?");
        if (!yes) return;
        //
        var qstring = {
            //
            dbname:this.dbname,
            tname:this.tname,
            //
            //Ensure the primary key is posted. Example of querying teh view 
            //part of a dom record
            primarykey: this.primarykey = dom_record.view.getAttribute("primarykey")
        
        };
        //
        //Execute the delete record method on the page_record object. 
        this.ajax("delete_record", qstring, "json", function(result){
            //
            if (result.status==="ok"){
                //
                //Rather than refreshing an entire page, simply remove/detacch 
                //the view of the dom_record from its parent. do some 
                //investigation around dom_record.view.getParent().remove(dom_record.view);
                dom_record.delete();
            }
            //
            //Otherwise show the error message
            else{
                this.show_error_msg(result);
            }
        }, "page_record");
    };
    
}



//A descendant is a special page of the page_record. It inherits 
//properties from the page_records; thst is why it shares the same js file as
//the page_records. 
//A descendant page has no phsical file equivalent
function page_descendant(input_){
    //
    //
    //Call the parent page_records (which is also defined in in this same file)
    page_records.call(this, input_);
    //
    //Let x be the querystring of the parent; I will ovrride it in the the
    //implementation of this version's querystring
    this.x = this.get_querystring;
    
    //Returns a query string for supporting CRUD operations on ercros of this 
    //page. This extends the page records version by adding a parent table name an dforeign key properties to the
    //parents querysring
    this.get_querystring = function(dom_record=null){//page_descendant
        //
        //Get the querystring of the parent page of records; x is the parent
        //query string before overring it.
        var qstring = this.x(dom_record);
        //
        //Add the parent table ame
        qstring.parent_tname = this.parent_tname;
        qstring.parent_primarykey = this.parent_primarykey;
        //
        //Return the richer query string
        return qstring;
    };    
    
}

//Representation of a login page class in JS. The justification of this page as 
//a standaone file is the fact that it is referenced in more than one place, e.g.,
//in page_login as well as in page_mutall
function page_login(input_) {
    //        
    //Login data is laid out in a label format. Initialize the page system.
    //The row index and its value are not important
    page.call(this, false, false, input_);
    
    //Save the login data by copying it from the dom record to the js 
    //record (structure) and saving it in the windows object ready for the 
    //caller to pick it up from there
    this.ok = function(){
        //
        //Get the record tagname of this page's layout
        var rec_tagname= this.layout.record_tag_name;
        //
        //Get the dom record view using the correct tag name. (For tabular layout
        //the tag name is "tr"; for labels, it is "field")
        var dom_record_view = window.document.querySelector(rec_tagname);
        //
        //Create a dom do record from this view and page; this process also 
        //transfers the valus from the view to the record's values
        var $dom_record = new dom_record(dom_record_view, this);
        //
        //Compile the querystring from the dom_record values; it comprises of 
        //the user name and password
        var qstring = {
            username: $dom_record.values.username,
            password: $dom_record.values.password
        };
        //
        //Use ajax to save the login credentials to the special session variable
        //i.e., not in the general mutall_id cache that is used for inter-page
        //communication. Then save the login data record so that the caller page
        //can access it to update its login status
        this.ajax("save_login", qstring, "json", function(result){
            //
            //Pass on the populated record to the caller js function if login 
            //credentials were succesfully saved to the server
            if (result.status==="ok"){
                //
                //Close this window properly; this means saving the compiled
                //querystring data to the windows object first, then closing it. 
                //That way, caller will have access to the data in the query 
                //string. When the window is improperly closed, the querystring
                //data is not saved, so that the caller cannot access it.
                this.close_window(qstring);
            }
            //...otherwise show the error message. The page remains open
            else{
                this.show_error_msg(result);
            }
        });
      };
      
    //Logout simply destroys the session variables
    this.logout = function(){
        //
        //Request for logout function; no data needs to besent to the server to 
        //logout
        this.ajax("logout", {}, "json", function(result){
            //
              if (result.extra==="ok"){
                //
                //Close the window. This is the event that signals to the caller 
                //that we are done with login
                window.close();
            }
            //...otherwise show the error message
            else{
                this.show_error_msg(result);
            }
        });
    };
    
    //
    //Cancel closes the login window with false set to the window mutall_id
    //so that the caller can tell that the login was indeed cancelled
    this.cancel = function()
    {
        //
        //Set the output flag to false
        window[this.mutall_id] = false;
        //  
        //Close the window. This is the event that tells the caller that we are
        //done
        window.close();
    };
    
    //The login page needs no initialization
    this.initialize = function(){};
    
    //Log into or out of the mutall database system and show the status on the 
    //appropriate buttons of this page. This allows access to specific databases
    this.log = function(is_login){
        //
        //Get the log in/out buttons
        var buttons = this.get_log_buttons();
        //
        //Do either login or logout
        if (is_login){
            this.login(buttons);
        }
        //
        else{
            this.logout(buttons);
        }

    };
    
    //Log into the mutall system to provide credentials that filter the 
    //databases that one is allowed access.
    this.login = function(buttons){
        //
        //Define the dimension specs of the login window in pixels
        var specs = "top=100, left=100, height=400, width=600";
        //
        //Open the login page with no requirements. If the login was
        //sucessful, we expect an object with the login credentials
        this.open_window("page_login", {}, function(login){
            //
            //Show the login status
            this.set_log_buttons(true, buttons, login.username);
            //
            //Update this page's username and password
            this.username = login.username;
            this.password = login.password;
        },specs);
    };
   
   //Get the log in/out buttons on the current page
    this.get_log_buttons = function(){
        //
        //Get the login button; 
        var login = window.document.getElementById("login");
        //
        //It must be found!.
        if (login===null){
            alert("Log in button not found on page "+ this.name);
        }
        //
        //Get the logout button
        var logout = window.document.getElementById("logout");
        //
        //It must be found!.
        if (logout===null){
            alert("Log out button not found on page "+ this.name);
        }
        //
        //Define and set the buttons structure
        var buttons = {
            login: login,
            logout:logout
        };
        //
        //Return the buttons
        return buttons;
    };
    
    //Set the log in/out buttons, depending on the login status
    this.set_log_buttons = function(is_login, buttons, username){
      //
      ///Show the log in status
      if (is_login){
        //
        //Hide the login button
        buttons.login.setAttribute("hidden", true);
        //
        //Show the logout button with username
        buttons.logout.removeAttribute("hidden");
        //
        //Attach the user name to the logout butom
        buttons.logout.value = "Logout " + username;  
      }
      //
      //Show the log out status
      else{
         //Show the login button
        buttons.login.removeAttribute("hidden");
        //
        //Hide the logout button
        buttons.logout.setAttribute("hidden", true); 
      }
    };
    
    
    //Log out of a mutall system; this simply destroys the 
    //sesson variables
    this.logout = function(buttons){
        //
        //Request for logout function from the server; there is no seed data.
        //This is a special case where the php file to serve is not the default
        //but the one of teh parent
        this.ajax("logout", {}, "json", function(result){
            //
            //Save the record if login credentials are ok...
            if (result.status==="ok"){
                //
                //Set the status
                this.set_log_buttons(false, buttons);
            }
            //...otherwise show the error message
            else{
                this.show_error_msg(result);
            }
        }, "page_login");
    };
    
    
}

//
//Js page constructor. The mutal_page_ is used by the client to 
//receive data from the server
function page_mutall(page_mutall_){
    //
    //The mutall pag inherites the login page in order to access
    //the login functionality
    page_login.call(this, page_mutall_);
    //
    //On loading this page, show the log in status
    this.initialize = function(){
        //
        //Get teh login buttons
        var buttons = this.get_log_buttons();
        //
        //Get the login sttaus from teh server
        var status = this.log_status;
        //
        //Set the login credentials, if they are known
        if (status.is_login){
            this.username = status.username;
            this.password = status.password;
        }
        //
        //Set the log in/out buttons
        this.set_log_buttons(status.is_login, buttons, status.username);
        //
        //Demo on how to extend a page
        this.show_clients();
    };

    //Demo on how to extend a page. Show the mutall data clients
    //Note the querystring used in html:-
    //page_records.php?tname=client&dbname=mutallco_data
    this.show_clients = function(){
        //
        //Create a jasvacript query string -- thh quivalent of version
        //tname=client&dbname=mutallco_data
        var querystring={
            tname:"client",
            dbname:"mutallco_data"
        };    
        //
        //Use ajax to execute the page_records.display_page() method using the
        //query string
        this.ajax("display_page", querystring, "html", function(html){
            //
            //Locate the element with the zones id
            var div = document.querySelector("#client");
            //
            //Skip this if
            //
            //Change the inner html to
            div.innerHTML = html;
        }, "page_records");

    };    

     //View the available databases; you must login to access them
    this.view_databases = function(){
        //
        //Check whether the user is logged in
        if (typeof this.username==="undefined"){
            //
            //Log in the user. Remember that this page inherits
            //the login page
            //
            //Do the login
            this.log(true);
        }
        // 
        //Wait for the user to complete the log in
        this.wait(
            //
            //waiting for you to...
            "complete the logging in to view the available databases",
            //
            //Define a test that succecds when the user has logged in
            "user_has_logged_in",
            //
            //Open the databases window after the login
            function(){
                //
                //Open the list of databases window with no requirements. On 
                //finish update the visited databaese in the windows local storage
                this.open_window("page_databases", {}, function(page_databases){
                    //
                    //Save the dbname index
                    //
                    //Get the index name
                    var name = page_databases.index;
                    //
                    //Get the index value
                    var value = page_databases[name];
                    //
                    //Transfer the index to this page
                    this[name]=value;
                });
            }
        );
    };

    //Succeeds if the user has logged in
    this.user_has_logged_in = function(){
        //
        if (typeof this.username==="undefined"){
            return  false;
        }
        //
        //Clear the wait message
        this.clear_error_msg();
        //
        return true;
    };

    //Serialize sql_edit for all the tables in all the databases.
    //Serialization helps to kep server specific ata to th server,
    //thus reducing the data traffic between it and the client.
    //This has 2 advantatages:-
    //1-It improves reponsiveness and hence the user experience
    //2-It simplifies the way the user requests for services, only
    //having to sepecify the minimimal data. This is useful when 
    //the users need to incorporate pages, derived via Buis, to 
    //thier websites
    //3-Following on from 2, it helps to bookmark pages for 
    //revisiting later.
    //This is a one off procedure that is run when teh database
    //is installed. Wehen modelling and data entr will be merged
    //we will need to control,this process much more precisely --
    //ratjer than carry it out fot the entire database.
    this.serialize_tables = function(){
        //
        //Confirm the serialization and continue if necessary.
        var yes = window.confirm("Do you really want to 'serialize' all the table?");
        if (!yes) return;
        //
        //Request the server for a 2-d array entities, a.k.a., 
        //tables "harvested" from the installed database on the 
        //local server the method is defined in class page_databases,
        //rather than on mutall_page because mutall_page is not
        //placed in the buis libary. This illustrates one reason
        //for sharing classes in a library. 
        this.ajax("get_entities", {}, "json", function(result){
            //
            //The array is kept in the extra property of the result
            //It ia an arry of records that has the following format:-
            //[['1','t1', 'db1'], [...],...,[...]
            var entities = result.extra;
            //
            //Get the number of entities to serialize
            var maxcounter = entities.length;
            //
            //"Serialize" each one of the entity
            for(var i in entities){
                //
                //Get the i'th entity
                var entity = entities[i];
                //
                //Serialize the i'th entity, up to the maximumn no
                this.serialize_entity(entity, i, maxcounter);

            }  
            //
        }, "page_databases"); 
    };

    //Serialize the sql_edit for each entity, a.k.a., table in 
    //the given database. It is the i'th entity of the maximum count
    this.serialize_entity = function(entity, i, maxcounter){
        //
        //Compile the query string needed to be sent to the server
        //to serialize teh entity
        var qstring = {
            //
            entity:entity[0],
            //
            tname: entity[1],
            //
            dbname: entity[2],
            //
            counter:i
        };
        //
        //The serialization method is defined by class
        //page_dataabse -- rather than this mutall object
        //because (1) the mutall_page is not defined in a 
        //library and (2) it is more natutal to home it there
        //than elsewhere
        this.ajax("serialize_entity", qstring, "json", function(result){//serialize_entity
            //
            //The extra data from result has the same components 
            //as the input query string
            var qstring2  = result.extra;
            //
            //Show ok after the last database is serialized. (Strict equality, 
            //===, is failing)
            if (qstring2.counter == maxcounter-1){
                //
                this.show_error_msg("Serialization Completed");
            }
            else{
                //
                //Compile the display message
               var msg = qstring2.counter + '/'+ maxcounter + ': '+qstring2.dbname +'.'+ qstring2.tname;
               //
               //The result, at least, has a dbname. Show it
               //the in the reporting window
                this.show_error_msg('Outputing...' + msg);
            }

        }, "page_database");

    };

    //Returns true if the given database is a system one, i.e., it 
    //is a known table in mysql
    this.is_system_dbname = function(dbname){
       //
        if ((dbname==="information_schema")
            || (dbname==="performance_schema")
            || (dbname==="phpmyadmin")
            || (dbname==="webauth")
            || (dbname==="mysql")
            ){
            //
            //Set yest to true
            return true;
        }else{
            //
            //This is not a system dbname
            return false;
        }
    };
}      


//Define the js version of a database page
function page_databases(page_databases_) {
    //
    //The row index of a scheme is simply the "dbname"; there is no special 
    //id tat we neeed to to to when we construct a tabular layout from the page 
    //of databases
    page.call(this, "dbname", false, page_databases_);
    //
    //
    //View the tables of the current (selected) database; if none is selected 
    //then wait for the user to do so; then view the related tables.
    this.view_tables = function (){
        //
        //Wait for the user to select a database and only proceed on success
        this.wait(
            //
            //Show this error message to prompt the user to select a database
            "select a database", 
            //
            //This is the function to test if a bname has been selected on this 
            //page
            "dbname_is_selected",
            //
            //Open the tables window based the on current on database 
            //selection. If the table page is closed properly successful, 
            //we need to save save the tabl'e index to this page and to
            //the windows local storage
            function(){
                //
                //On;y the dbname component is required for opening a list
                //of tables
                var requirements = {
                    dbname: this.dbname
                };
                //
                //Open the tables page with dbase as the only parameter, then  
                //update the property 'tname' on finish
                this.open_window("page_database", requirements, function(page_database){
                    //
                    //Save the dbname index
                    //
                    //Get the index name
                    var name = page_database.index;
                    //
                    //Get the index value
                    var value = page_database[name];
                    //
                    //Do the saving to teh local windows storage
                    this[name]=value;
                });
            }
        );
    };

    //Returns true if a dbname is selected; otherwise its false.
    this.dbname_is_selected = function(){
        //
        //Get this pages index
        var index = this.index;
        //
        //Use the index name to define condition for a selected record 
        var selected = this[index]!=="undefined" && this[index]!==null;
        //
        return  selected ? true: false;
    };
}

//Define the constructor for page of tables. Note that this function is 
//misaligned deliberately to so that the with of its text is less than the
//left edge marker
function page_database(page_database_){
    //
    //Initialize the page with no specific onload id for the tname
    //index
    page.call(this, "tname", false, page_database_);
    //
    //Set the table name; this function is called by page.view_records
    //to signal the end of a wait to select a table whose records we want
    //to view.
    this.set_table_name = function(){
        //
        //Try to get the current dom record; this fails quietly if 
        //none is selected
        var dr = this.try_current_dom_record();
        //
        //If a record selection is found, then set this page's table name 
        //and return true
        if (dr){
            //
            //Set this tname by transferring it from dom to this page
            this.tname = dr.view.getAttribute('id');
            //
            //Return the true status
            return true;
        }
        //
        //Otherwise return false, i.e., when no dom recrod is selected
        return false;
    };

}

 //Representation of a selector page in js; note the delibarate misalignment
function page_selector(page_selector_) {
    //        
    //Call the inherited page of records
    page_records.call(this, page_selector_);

    //Extend the querystring with the arguments of a page selector constructor
    this.extend_querystring = function(qstring){
        //
        //The properties that extends a page of records to a slector page
        qstring.id = this.id;
        qstring.primarykey = this.primary;
        qstring.output = this.output;
    };
    
        
    //Use the given hint to search the primary key field (in the output subfield)
    //for the hinted records of this page's driver table. 
    this.search_hint = function(hint){
        //
        //Formulate the query string requirements for evoking the display page 
        //using the search view
        var qstring = {
            //
            //Set arguments of page_records constructor; they are important for
            //retrieving teh serialized sql_edit
            tname:this.tname,
            dbname:this.dbname,
            //
            //The arguments of this method in the search view.
            //
            //Set the hint value to search for.
            hint: hint,
            //
            //Start display from the fisrt record
            offset:0,
            //
            //Show the headers in the initial search
            body_only:false,
            //
            //Show as many records a are needed for the scroll bars to appear; 
            //otherwise they wont. Too high a number is also bad, because ot 
            //would reduce the responsiveness when the search results are 
            //initially reported.
            limit:this.full_limit,
            //
            //Ensures that the returned results have the same style as this page
            layout_type: this.layout.type,
            mode_type:this.mode.type
        };
        //
        //Add to the query string the fields that extends page_records to 
        this.extend_querystring(qstring);
        //
        //Populate the dom node that corresponds to this page with html 
        //resulting from executing the search on the server (using the ajax 
        //method)
        this.refresh("search_hint", qstring);
    };
    
   //Return the values, i.e., the id, primary and output subfields of the 
    //current dom record to the caller. This effectivly saves a copy of
    //the values to the current windows object and closes it. The caller will 
    //access the values from the window's object. 
    //NB. Closing the window object does not destroy it -- that's why we can use
    //it to share pages on same client.
    this.return_field = function (){//edit_fkfield
        //
        //Get the currently selected dom record
        var dom_record = this.get_current_dom_record();
        //
        //Debugging
        console.log(dom_record);
        //
        //Retrieve the primary key field from this dom record
        //
        //Get the name of the primary key field of the dom record; it is the 
        //same as that of the dom record's table name
        var pkfname = dom_record.tname;
        //
        //Now get the primary key field
        var pkfield = dom_record.fields[pkfname];
        //
        //Let values be an empty object for collecting the dom field's vlues
        var values = {};
        //
        //Copy the values from the (true) dom_field to the values collector
        pkfield.copy(true, this, dom_record.view, values);
        //
        //Attach the values to the field for onward transmission; the values
        //would be meaningless without the field
        pkfield.values = values;
        //
        //Close the window, returning the field with the attached values
        this.close_window(pkfield);
    };
}

//
//The javascript "class" that models the functionality of a
//page of a single record. The input_ is non-object data passed
//on from the php environment and used to compile the page
function page_record(input_) {
    //
    //Call the parent page_records class
    page_records.call(this, input_);
    //
    //Let x be the page activation class, before it is modified. Why do we
    //need to override it? This does not look correct?? See the reason for
    //overriding below.
    var x = this.activate_class;

    //Override the activate class defined in the grand parent 
    //mutall so that this record+page can activate the descendants
    //This is important because, to be useful in the js environment, 
    //and noting that a descendant is constructed in the php environmet, 
    //we need to activate it. We aim to achieve this without 
    //modifying the base page class by overriding the activate 
    //class method
    this.activate_class=function(classname, input){
        //
        //The targeted class name to be treated specially is 
        //descendant. Put this activation code in irts proper class!!!
        if (classname==="page_descendant"){
            return new page_descendant(input);
        }
        //
        //Call original version of the activate class
        var aclass = x.call(this, classname, input);
        //
        //Return the active result
        return aclass;
    };
    //
    //Activate the descendants
    this.descendants = this.activate(input_.descendants);

    //
    //Edit the current field of the current record of the current
    //descendant. Note the repetition of the word "current"
    this.edit_field = function(){//page_record
        //
        //Get the current dom field
        var df = this.get_current_dom_field();
        //
        //Check if the dom field has the dom descendant ancestor
        var dom_descendant = df.closest("descendant");
        //
        //This is not a descendant; use the parent's edit field 
        //method
        if (dom_descendant===null){
            //
            //Invoke the receord_page parent's edit function 
            var p = new page();
            //
            //Call the normal page edit field using the context
            //of the record page
            p.edit_field.call(page_record);
        }
        //This is a descendant
        else{
            //Retrieve the table name
            var tname = dom_descendant.getAttribute("id");
            //
            //Use the globally available record page variable to 
            //access the required descendant
            var js_descendant = page_record.descendants[tname];
            //
            //Use this descendant to guide the editing
            js_descendant.edit_field();
        }
    };
    
    //
    //Let x be the querystring of the parent; I will ovrride it in the the
    //implementation of this version's querystring
    this.old_get_querystring = this.get_querystring;
    
    //Returns a query string for supporting CRUD operations on this 
    //page. This extends the page records version by adding a primary key of
    //the table
    this.get_querystring = function(dom_record=null){//page_descendant
        //
        //Get the querystring of the parent page of records; x is the parent
        //query string before overring it.
        var qstring = this.old_get_querystring(dom_record);
        //
        //Add the primary key of this record
        qstring.primarykey = this.primarykey;
        //
        //Return the richer query string
        return qstring;
    };    
    

    //Override the default page initialize function by including code that 
    //populates the descendants of this page record with data
    this.initialize = function(){//show_descendant
        //
        //Get the descendants node by searching from the entire document
        var des_node = window.document.querySelector("descendants");
        //
        //Step through every descendant and paint its page in this window
        for(var tname in this.descendants){
            //
            //Set the data required to show descendant
            var qstring = {
                //
                //Set the descendant's table name; it is the extra data 
                //required over and above that of this parent record
                tname:tname,
                //
                //Set the name of the underlying database; all teh following data
                //comes from this page_record
                dbname:this.dbname,
                //
                //Set the name of the parent table name
                parent_tname:this.tname,
                //
                //Set the primary key of the parent table
                parent_primarykey:this.primarykey
            };
            
            //
            //Create the descendant page and display it. Ajax is used because we
            //want 2 outputs, viz., (1) the html for dislaying the page and (2) 
            //the js data structure for enriching this page in order to support 
            //further interactions. Note how we override the current classname
            this.ajax("show_descendant", qstring, "json", function(result){
                //
                //Save the descendant data to its correct place. NOTE THAT THIS
                //IS AN ASYNCHRONOUS OPERATION, SO THE tname ABOVE MAY NOT BE 
                //NECESSARILY THE ONE WE WANT FOR THIS FUNCTION. Hence put it in
                //the result. Remember to activate it
                this.descendants[result.extra.tname] = this.activate(result.extra.data);
                //
                //Add the html to the children of descendants node
                //
                //Create a dummy element; you can call it anything bacsuse we 
                //will overwrite it using the outerHTML property below
                var a = window.document.createElement("descendant");
                //
                //Attach the page to the descendant node in preparation for 
                //changing ints out html (which you canot do if a has no parent)
                des_node.appendChild(a);
                //
                //Now set the outer html of the page to that of the incoming 
                //result; see previous comment on outerHTML
                a.outerHTML = result.html;
            }, "page_descendant");
        }
    };

    //
    //On clicking some field on this page, execute the requested method. This 
    //operation determines if the clicking was done on a parent record object 
    //or on one of her descendants. 
    this.onclick_field = function(method){
        //
        //Let page be the object for which we need to execute the method. By 
        //defaut, no page is selected
        var page=false;
        //
        //Get the current dom field by searching the entire document for the 
        //element with class field because by design, there should be only one 
        //such element in a page.
        var df = this.try_current_dom_field();
        //
        if (!df){
            //
            //There is no dom record found. It may be that 
            //- no record is actually selected
            //- a dependant is selectec but not any of its records; perhaps it 
            //  has none. Determine if it is the latter case.
           page = this.try_current_js_descendant();
        }
        else{
            //
            //Dermine if the selected dom record is a page record or one of her 
            //descendants
            //
            //Check if the dom field has a dom descendant ancestor
            var dom_descendant = df.closest("descendant");
            //
            if (dom_descendant===null){
                //
                //This is not a descendant, so we assume that the dom record is on 
                //this page that is associated with the global variable, page_rcord,
                //
                //Invoke the page_record' with the rwquested function 
                page = page_record;
            }
            //This is a descendant; perform the action on a descendant
            //page. Which one?
            else{
                page = this.try_current_js_descendant();
            }
        }
        //
        //If the page is valid, execute the requested method on teh correct 
        //object
        if (page){
            //
            page[method]();
        }
    };


    //Returns the current dom descendant of this page based on the "current" 
    //attribute. An alert is provided if there is no current selection
    this.get_current_dom_descendant = function (){
        //
        //Try to get the current dom descendant
        var dd = this.try_current_dom_descendant();
        //
        if (!dd){
            alert ("There is no current descendant selection");
            return false;
        }
        //
        //Return the dom descendant
        return dd;
    };

    //Returns the current dom descendant of this page based on the "current" 
    //attribute. No alert is provided if there is no current selection.
    this.try_current_dom_descendant = function (){
        //
        //Formulate the css selector for the current descendant
        var dselector = "[current='descendant']";
        //
        //Retrieve the current dom descendant, searching from the entire 
        //document.
        var dd = window.document.querySelector(dselector);
        //
        if (dd ===null)
        {
            return false;
        }
        //
        //Return the descendant
        return dd;
    };



    //Returns the current js descendant, alerting the user if there
    //is none. Related to this is the get current dom descendant
    //and the php page_descendant
    this.get_current_js_descendant = function (){
        //
        //Try to get the current js descendant
        var jd = this.try_current_js_descendant();
        //
        if (!jd){
            alert ("There is no current descendant selection");
            return false;
        }
        //
        //Return the dom descendant
        return jd;
    };

     //Returns the current js descendant of this page based on the "current" 
    //attribute. No alert is provided if there is no current selection.
    this.try_current_js_descendant = function (){
        //
        //Formulate the css selector for the current descendant
        var dd = this.try_current_dom_descendant();
        //
        if (!dd)
        {
            return false;
        }
        //
        //Retrieve the js decsendant from the dom version
        //
        //Get the descendant's table name
        var tname = dd.getAttribute('id');
        //
        //Rerieve the descendant indexd by the table name
        var jd = this.descendants[tname];
        //
        //Retur the js descendant
        return jd;
    };
}

