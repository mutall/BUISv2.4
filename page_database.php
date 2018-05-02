<?php
// This page assumes that the user has already logged in to a database. It opens
// that database and lists all the mutall tables that are in it. The tables 
// are used to navigate around the database
//
//The page_database class that models this page is defiend in teh BUIS libray
require_once "buis.php";
//
//Retrieve $_GET variable indirectly to avoid the warning about access to global 
//variables
$qstring = querystring::create(INPUT_GET);
//
//Create an instance of this page using the posted global variable, $_GET
$page_database= new page_database($qstring);
?>
<html>
    <head>
        <title>View Tables</title>

        <link rel="stylesheet" type="text/css" href="main.css">
        
        <!-- Include the core mutall library-->
        <script id='mutalljs' src="mutall.js"></script>

        <!-- Include the extension to the core mutall library-->
        <script id='buis' src="buis.js"></script>

        <!--Script for defining the objects needed for interacting with this page-->
        <script id='js'>
            //
            //Create a js page_database object. (Note how echoing a mutall obhect
            //produces a checked kjson string)
            var page_database = new page_database(<?php echo $page_database; ?>);
        </script>


    </head>
    <body onload="page_database.initialize()">

        <!-- The header section -->
        <header>
        </header>

        <!-- The articles section. -->
        <article>
            <?php
            //
            //Display this page using teh local settings provided during 
            //construction
            $page_database->display_page();
            ?>
        </article>

        <!-- The footer section -->
        <footer>
            <!-- View the current table's records -->
            <input id='view_records' type="button" value="View Records" onclick='page_database.view_records()'>

            <!-- Close this page properly-->
            <input id='close_page' type="button" value="Close" onclick='page_database.close_window()'>


            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'/>

        </footer>
    </body>

</html>
 