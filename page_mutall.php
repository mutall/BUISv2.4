<?php
//The mutall page is special in a number of ways
//1 It is the home page of the Mutall data company (from which all data services
//  offered by Mutall can be accessed
//2 Its PHP, Javascript and HTML implementstions are all contained in the
//  same file. This means 2 things:=
//  a)  The key components of an interative page can be easily demonstrated 
//      using Netbeans navigator panel
//  b)  The page_class and cannot be shared (via include); thats not a good
//      programming practice. There are 2 ways to solve this: 
//      i) pull out the php and js class implementations into standalone files 
//      ii) put the php and js versions into a common shared lirary, buis.php
//  To keep the number of files manageable, we have adopted the 2nd approach
//  to develop this project.    
//
//Start a sesssion and avail the Brod User Interactive System libray including the
//core mutall one
require_once "buis.php";

//
//Prepare to create an instance of this page
//
//Use the empty array to define a querystring
$querystring = new querystring([]);
//
//Create an instance of this page; ou can name the variable anyting you wish
//as long as you refer to the same in the execute function below.
$page_mutall= new page_mutall($querystring);
?>
<html>
    <head>
        <title>mutall page</title>

        <!-- Style sheet shared by all mutall pages -->
        <link id="mutallcss" rel="stylesheet" type="text/css" href="mutall.css"/>

        <!-- Style sheet specific to this page -->
        <link id="page_mutall_css" rel="stylesheet" type="text/css" href="page_mutall.css"/>

        <!-- Script for referencing the prototypes for objects needed for 
        interacting with this page -->
        <script id='mutall' src="mutall.js"></script>
        <!--
        Gain access to log in/out functionality. This demonstrates how to
        re-use pages in OO-->
        <script id='page_login' src="buis.js"></script>

        <!--Script for defining the objects needed for interacting with this page-->
        <script id='js'>
             //
            //Create a js page_mutall object. (Note how echoing a mutall object
            //produces a checked json string)
            var page_mutall = new page_mutall(<?php echo $page_mutall; ?>);
        </script>

    </head>
    <!-- Initialize the page , starting with the login status -->    
    <body onload="page_mutall.initialize()">

        <!-- The header section -->
        <header>
           
        </header>

        <!-- The articles section. -->
        <article>
            <records>
                <record id="id">
                    <caption_>
                        Who we are
                    </caption_> 
                    <tagline>
                        <b>mutall_data</b> is a program of <a href='mutall.co.ke/rental'>Mutall Investment Company Ltd</a>
                        that mentors young college graduates to become skilful data
                        managers in business.
                    </tagline>
                    <img src="pictures/data modeling 2.jpg" alt=""/>
                </record>

                <record id="mission">
                    <caption_>
                        Our Mission
                    </caption_>
                    <tagline>To provide a service that serves your service for better performance.
                    </tagline>
                    <img src="pictures/images 1.jpg" alt=""/>
                </record>
                <record id="vision">    
                    <caption_>
                        Our Vision
                    </caption_> 
                    <tagline>To lead and guide you into a new era of data management.
                    </tagline>
                    <img src="pictures/1-cIHErwIINhL0eRp1xee4_Q.jpeg" alt="" />
                </record>
                <record id="values">
                    <caption_>
                        Our Values
                    </caption_>
                    <tagline>
                        Work at own pace
                        You don't have to be at your desk to be seen to work
                        You have to get stuck to learn something new
                        We enjoy solving real problems 
                    </tagline>
                     <img src="pictures/images (14).jpg" alt="" />
                </record>

                <record id="passion">
                    <caption_>
                        Our Passion
                    </caption_>

                    <tagline>We love to identify, develop, deploy and support(IDDS) data 
                        management services to small enterprises. This means:-
                        Data modellings
                        Data Interrogation
                        Data Reporting
                        Join Training Sessions
                        
                    </tagline>
                    <img src="pictures/Data modeling 1.jpg" alt=""/>
                </record>
                <record id="clients">
                    <caption_>
                        Our Clients
                    </caption_>
                    <tagline>Our clients are small enterprises because we believe
                        - that they provide better data management experience for
                        trainees than lage organisations that already run an IT
                        department.
                        - that that is the area we are likely make impact
                        <p id="client">Clients list inserted here</p>
                    </tagline>    
                </record>
                
                <record id="services">
                    <caption_>
                        Our Services
                    </caption_>
                    <tagline>
                        Website design & hosting
                        Sending Bulk SMS
                        Training...
                        --
                        Client Statements by Brian
                        Water Meter Readings by Samuel
                        Client Registration by Raphael
                        KPLC Electricity Bills
                        Rental Client Agreements/Termination Wycliffe
                        --
                    </tagline>    
                    <img src="pictures/team building 2.jpg" alt=""/>
                </record>


                <record id="staff">     
                    <caption_>
                        Our Staff
                    </caption_>
                    <tagline>
                        (Insert here the staff profiles and their images)
                        
                    </tagline>
                    <img src="pictures/DSCF3805.JPG" alt=""  />
                    <img src="pictures/DSCF3804.JPG" alt="" />
                    <img src="pictures/DSCF3807.JPG" alt="" />
                </record>
                <record id="funding">         
                    <caption_>
                        Our Funding
                    </caption_>
                    <tagline>
                        The Client/Mentee Sponsorship aspect.
                        The Client registration/subscription system.
                        The Guinea Pig Approach
                    </tagline>

                </record>

                <record id="contacts">
                    <caption_>
                        Our Contacts
                    </caption_>
                    <tagline>We are located in a small growing town in Kajiado and
                        hence there comes the interest to develop small businesses.
                        The town is in the heart of Kajiado, a junction between major
                        towns, giving all a meeting point for business, family outings and other activities
                    </tagline>
                    <img src="pictures/_20180312_123806.JPG" alt="" />
                </record>
                <record id="tools">
                    <caption_>
                        Our Tools
                    </caption_>
                    <tagline>
                       The Mutall Studio
                       Broad User Interface System (Buis)
                       Prolog/PHP/Sql/Javascript/HTML/CSS
                    </tagline>
                </record>
                <record id="method">
                    <caption_>
                        Methods
                    </caption_>
                    <tagline>
                        Seminars<br/>
                        -How to Setup BUIS<br/>
                        .Install On Server:-<br/>
                        ..Empty Copy of mutall_data Database<br/>
                        ..Buis Version 2.4<br/>
                        .Install On Client:-<br/>
                        ..Latest Mutall Studio<br/>
                        ..The Metadata Script
                        .Harvest Metadata from Server to mutall_data Database<br/>
                        .Serialize the Databases<br/>
                       Assignments <br/>
                       One-on-one Tutorials<br/>
                       Group Reviews<br/>
                    </tagline>
                </record>
                <record id="seminars">
                    <caption_>
                        Seminar Series
                    </caption_>
                    <tagline>
                      <listitems> 
                       <li>The Anatomy of a Buis Page</li>
                       <li>The Buis Object Data Model</li>
                       <li>Navigating the Buis System</li>
                       <li>Extending Buis to Services</li>
                      <listitems> 

                    </tagline>
                </record>
                <record id="bugs">
                    <caption_>
                        Our Bug Tracking Page
                    </caption_>
                    <tagline>
                       
                    </tagline>
                </record>
                <record id="opportunities">
                    <caption_>
                        Data Management Opportunities
                    </caption_>
                    <tagline>
                       What are Employers Looking for?
                    </tagline>
                </record>
            </records>
            
        </article>

        <!-- The footer section -->
        <footer>
            <!--
            Log in to access the allowed database; its more flexible to use 
            this method than the anchor <a href=.../> version. For instance, 
            we can :-
            a) specify the size of the login pop up window
            b) show the username on successful login
            The log in/out functions are found in the page_login object
            which is inherited by page_mutall-->
            <input type="button" value="Login" id="login" onclick='page_mutall.log(true)'/>
            <input type="button" value="Logout" hidden='true' id="logout" onclick='page_mutall.log(false)'/>

            <!-- View the available databases. The flexibility of mutall's 
            method comes into play when:-
            (a) we have to prompt the user to login if not yet
            (b) show list of accessible databases after successful login
            This would is the common (not very flexible) way of evoking the databases
            page:-
            <a id='view_databases' href='page_databases.php'>View Databases</a> 
            -->
            <input type="button" value="View Databases" id="view_databases" onclick='page_mutall.view_databases()'>

            <!-- Serialize tables from all teh databases-->
            <input type="button" value="Serialize Tables" id="serialize_tables" onclick='page_mutall.serialize_tables()'>

            <!-- This tag is needed for reporting mutall errors. On clicking
            clear the error--> 
            <p id='error' onclick='this.innerHTML=""'/>

        </footer>
    </body>

</html>
    