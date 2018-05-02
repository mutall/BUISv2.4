<?php
//page_record is an example of a complex page. Complex because it has pages
//within a page with different layouts and display. For instance that the fields 
//the parent record are dislayed in a label format while her (foreign key 
//descendant are displayed in a tabular format.
//We are using ths page to study the issues involved in developing complex pages. 
//
//Include the mutall library that comprises of code shared between pges
//The record page makes referece to the descedatnt page. Include it
require_once 'buis.php';
//
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Create a new instance of this (complex)page; its construtor provides the minimum
//requirements of such a page that must be supplied by the posted data.
$page_record= new page_record($qstring);

?>

<!-- The html part of this page -->
<html>
    <head>
        <title>Record <?php echo $page_record->tname; ?></title>

        <link rel="stylesheet" type="text/css" href="main.css"/>
        

        <!-- Script for referencing the prototypes for objects needed for 
        interacting with this page -->
        <script id='mutalljs' src="mutall.js"></script>

        <!-- Resolve the page_records and page_descendants -->
        <script id='buisjs' src="buis.js"></script>

        <!--Script for defining the objects needed for interacting with 
        this page-->
        <script id='page'>
            //
            //The PHP/Javascript interface
            //
            //Create an instance of this page in js. Note how the PHP 
            //instance data is passed to the javascript environment
            var page_record = new page_record(<?php echo $page_record; ?>);
            //
            //Debug
            //console.log(page_record);
            
        </script>
    </head>
    <!-- 
    Once the body is loaded, set the necessary dom elements to 
    display their initial status, including a complete build of the 
    page. This only works if document was loaded using the get method!-->
    <body onload="page_record.initialize()">

        <!-- The header section -->
        <header>
         </header>

        <article>

            <?php
            //
            //Display this paage
            $page_record->display_page();
            ?>
        </article>

        <!-- The footer section -->
        <footer>

            <!--Edit the current field-->
            <input id=edit_field type=button value="Edit Field" onclick="page_record.onclick_field('edit_field')"/>

            <!-- Save the selected javascript record (indirectly)-->
            <input id='save_current_record' type="button" value="Save Record" onclick="page_record.onclick_field('save_current_record')">

            <!--Edit the current record-->
            <input id=edit_record type=button value="Edit Record" onclick="page_record.onclick_field('edit_record')"/>

            <!-- Add a new record; remember to pass the parent page_record
            in order to be able to create a partially filled (with foreign key values) new record-->
            <input id=add_record type="button" value="Add Descen. Record" onclick='page_record.get_current_js_descendant().add_record()'>

            <!-- View the detailed record of the selected descendant -->
            <input id=view_record type="button" value="View record" onclick='page_record.get_current_js_descendant().view_record()'>


            <!-- Delete recordable-->
            <input id=delete_record type="button" value="Delete Descen. Record" onclick='page_record.get_current_js_descendant().delete_record()'>

            <!-- Cancel the record or field edit operation-->
            <input id=cancel_edit type="button" value="Cancel Edit" onclick="page_record.onclick_field('cancel_edit')">

            <!-- Cancel the window, i.e., closing it without affecting.  
            the caller-->
            <input id=close type=button value="Cancel Page" onclick="window.close()"/>

            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'></p>
        </footer>
    </body>

</html>
