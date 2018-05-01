<?php
//
//View the records of some table in a desired style
//
//The page_ecords class used to drive this page is defiend in the extended 
//library that supporta a broda set of useres o interact with this page 
require_once "buis.php";

//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Create an instance of this page of records and assum that all the 
//required inputs have been supplied via a query string. This allows us to 
//incorporate this page in a website
$prp= new page_records($qstring);
//
?>
<html>

    <head>
        <title><?php echo $prp->tname; ?></title>

        <link id="mutallcss" rel="stylesheet" type="text/css" href="mutall.css">

        <!-- Script for referencing the prototypes for objects needed for 
        interacting with this page -->
        <script id='mutalljs' src="mutall.js"></script>

        <!--Script that defines the page_records class-->
        <script id='page_records' src="buis.js"></script>

        <!--Script for defining the objects needed for interacting with this page-->
        <script id='page'>
            //
            //Create an active js page of records around which the related methods 
            //will be organized.
            var prj = new page_records(<?php echo $prp; ?>);
        </script>

    </head>
    <body onload="prj.initialize()">

        <!-- The header section -->
        <header>
            <!-- Button for search criteria-->
            <div>
                <label for ="criteria">Search Criteria</label>
                <input type ="text" id="criteria" size="80"/>
                <!--
                Execute the a search on hitting the enter key. The searched 
                value will be read off the criteria input field -->
                <input type ="button" id="search" value='Search' onclick="prj.search_criteria()" />

                <label for ="order_by">Order by</label>
                <input type ="text" id="order_by"/>
            
                <!--Hiding undesired fields -->
            
                <label for ="hidden_fields">Hidden Fields</label>
                <input type ="text" id="hidden_fields"/>
            </div>
            
            <!-- Changing the layout --> 
            <div>
                Layout Format:
                <label for='tabular'>tabular</label>
                <input type ='radio' id='tabular' name='layout' value='true' onclick="prj.use_style('layout', 'tabular')" />
                <label for='label'>label</label>
                <input type ='radio' id='label' name='layout' value='false' onclick="prj.use_style('layout', 'label')" />
            </div>

            <!-- Changing the display mode--> 
            <div>
                Display mode:
                <label for='output'>Output</label>
                <input type ='radio' id='output' name='mode' value='true' onclick="prj.use_style('mode','mode_output')" />
                <label for='label'>Input</label>
                <input type ='radio' id='input' name='mode' value='false' onclick="prj.use_style('mode','mode_input')" />
            </div>

        </header>

        <!-- Capture  the onscroll event (vertical) for this articles node -->
        <article onscroll = "prj.vscroll(this)">

            <?php
            //
            //Display this page using the local settings, i.e.,layout and mode, 
            // defined during construction
            $prp->display_page();
            ?>
        </article>

        <!-- The footer section -->
        <footer>
            <!-- View a detailed version of the selected record -->
            <input id=view_record type="button" value="View record" onclick='prj.view_record()'>

            <!-- Edit the current field selection-->
            <input id=edit_field type="button" value="Edit Current field" onclick='prj.edit_field()'>

            <!-- Add a new record-->
            <input id=add_record type="button" value="Add New record" onclick='prj.add_record()'>

            <!-- Modify the current record-->
            <input id=edit_record type="button" value="Modify record" onclick='prj.edit_record()'>

            <!-- Save the selected record -->
            <input id=save_current_record type="button" value="Save record" onclick='prj.save_current_record()'>

            <input id=delete_record type="button" value="Delete record" onclick="prj.delete_record()">

            <!-- Cancel the record/field edit operation-->
            <input id=cancel_edit type="button" value="Cancel Edit" onclick='prj.cancel_edit()'>

            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'></p>

        </footer>

    </body>

</html>
