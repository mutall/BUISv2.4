<?php
//A selector page allows us to select records of a foreign key field when
//doing data entry. Only the output component of the primary key field of the 
//foreign table is shown. This compnent is derived from all the identification 
//and friendly fields of the table. 
//
//We include the extesion to the Mutall library where page_selector class is 
//defined
require_once 'buis.php';
//
//Create a instance of a a page_selector.
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Now create the selector page instance
$page_selector= new page_selector($qstring);
?>

<html>
    <head>
        <title>Select <?php echo $page_selector->tname; ?></title>

        <link rel="stylesheet" type="text/css" href="mutall.css">

        <!-- Script for resolving references to the javascript page_selector
        and other related "classes"-->
        <script id='mutall' src="mutall.js"></script>
        <script id='buis' src="buis.js"></script>

        <!--Script for creating the js object, viz., page_selector,  needed for 
        supporting interactions with this page-->
        <script id='page'>
            //
            var page_selector = new page_selector(<?php echo $page_selector; ?>);
        </script>


    </head>
    
    <!-- Once the body is loaded, show the output subfield in the last selected
    field, for ease of reference-->
    <body onload="page_selector.initialize()">

        <!-- The header section -->
        <header>
            <!-- Button for client criteria -->
            <div>
                <label for ="criteria">Filter Client</label>
                <!--
                Do a search on the primary key field using the hinted value
                -->
                <input type ="text" id="criteria" onkeyup="page_selector.search_hint(this.value)"/>
            </div>
            
            <!-- Button for the last selection-->
            <div>
                <!-- The value of this id is the output component of the selector-->
              Last Selection:<?php echo $page_selector->output; ?>
            </div>
        </header>

        <article>

            <?php
            //
            //Display the the record selector ung he local settings
            $page_selector->display_page();
            ?>
        </article>

        <!-- The footer section -->
        <footer>
            <!-- Return the selected record to the caller-->
            <input id=return_field type="button" value="Return Selected field" onclick='page_selector.return_field()'>

            <!-- View/interact the current table's detailed records -->
            <input id='view_records' type="button" value="View Records" onclick='page_selector.view_records()'>

            <!-- Abort the selection -->
            <input id=cancel type=button value="Cancel" onclick="window.close()"/>
        </footer>
    </body>

</html>


