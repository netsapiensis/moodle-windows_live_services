<?php

/*******************************************************************************
Copyright (C) 2009  Microsoft Corporation. All rights reserved.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*******************************************************************************/

global $CFG;
include($CFG->dirroot.'/blocks/live_services/shared/utils.php');
?>
<html>
<head>
    <style>
        div#faq
        {
            margin:18px;
        }
        h1
        {
            font-size:1.3em;
        }
        
        h2
        {
            font-size:1.1em;
        }
        h3
        {
            font-size:1.0em;
        }
        a.questionLink
        {
            display:block;
            margin:10px 4px;
        }
    </style>
</head>
<body>
    <h1><?php echo(getLocalizedString('faqPageHeader')); ?></h1>
    <hr/>
    <p><?php echo(getLocalizedString('faqPageIntro'));?></p>
    <h2>Windows Live ID</h2>
    <a href="#WLID1" class="questionLink"><?php echo(getLocalizedString('faqWLIDQ1'));?></a>
    <a href="#WLID2" class="questionLink"><?php echo(getLocalizedString('faqWLIDQ2'));?></a>
    <a href="#WLID3" class="questionLink"><?php echo(getLocalizedString('faqWLIDQ3'));?></a>
    <a href="#WLID4" class="questionLink"><?php echo(getLocalizedString('faqWLIDQ4'));?></a>
    <h2>Moodle</h2>
    <a href="#Moodle1" class="questionLink"><?php echo(getLocalizedString('faqMoodleQ1'));?></a>
    <a href="#Moodle2" class="questionLink"><?php echo(getLocalizedString('faqMoodleQ2'));?></a>
    <a href="#Moodle3" class="questionLink"><?php echo(getLocalizedString('faqMoodleQ3'));?></a>
    <a href="#Moodle4" class="questionLink"><?php echo(getLocalizedString('faqMoodleQ4'));?></a>
    <a href="#Moodle5" class="questionLink"><?php echo(getLocalizedString('faqMoodleQ5'));?></a>
    <h2>Windows Live Services</h2>
    <a href="#WLS1" class="questionLink"><?php echo(getLocalizedString('faqWLSQ1'));?></a>
    <a href="#WLS2" class="questionLink"><?php echo(getLocalizedString('faqWLSQ2'));?></a>
    <a href="#WLS3" class="questionLink"><?php echo(getLocalizedString('faqWLSQ3'));?></a>
    <a href="#WLS4" class="questionLink"><?php echo(getLocalizedString('faqWLSQ4'));?></a>
    <a href="#WLS5" class="questionLink"><?php echo(getLocalizedString('faqWLSQ5'));?></a>
    <a href="#WLS6" class="questionLink"><?php echo(getLocalizedString('faqWLSQ6'));?></a>
    <a href="#WLS7" class="questionLink"><?php echo(getLocalizedString('faqWLSQ7'));?></a>
    <a href="#WLS8" class="questionLink"><?php echo(getLocalizedString('faqWLSQ8'));?></a>
    <a href="#WLS9" class="questionLink"><?php echo(getLocalizedString('faqWLSQ9'));?></a>
    <a href="#WLS10" class="questionLink"><?php echo(getLocalizedString('faqWLSQ10'));?></a>
    <a href="#WLS11" class="questionLink"><?php echo(getLocalizedString('faqWLSQ11'));?></a>
    <a href="#WLS12" class="questionLink"><?php echo(getLocalizedString('faqWLSQ12'));?></a>
    <a href="#WLS13" class="questionLink"><?php echo(getLocalizedString('faqWLSQ13'));?></a>
    <h2>Windows Live ID</h2>
    <h3 id="WLID1"><?php echo(getLocalizedString('faqWLIDQ1'));?></h3>
    <p><?php echo(getLocalizedString('faqWLIDA1'));?></p>
    <h3 id="WLID2"><?php echo(getLocalizedString('faqWLIDQ2'));?></h3>
    <p><?php echo(getLocalizedString('faqWLIDA2'));?></p>
    <h3 id="WLID3"><?php echo(getLocalizedString('faqWLIDQ3'));?></h3>
    <p><?php echo(getLocalizedString('faqWLIDA3'));?></p>
    <h3 id="WLID4"><?php echo(getLocalizedString('faqWLIDQ4'));?></h3>
    <p><?php echo(getLocalizedString('faqWLIDA4'));?></p>
    <h2>Moodle</h2>
    <h3 id="Moodle1"><?php echo(getLocalizedString('faqMoodleQ1'));?></h3>
    <p><?php echo(getLocalizedString('faqMoodleA1'));?></p>
    <h3 id="Moodle2"><?php echo(getLocalizedString('faqMoodleQ2'));?></h3>
    <p><?php echo(getLocalizedString('faqMoodleA2'));?></p>
    <h3 id="Moodle3"><?php echo(getLocalizedString('faqMoodleQ3'));?></h3>
    <p><?php echo(getLocalizedString('faqMoodleA3'));?></p>
    <h3 id="Moodle4"><?php echo(getLocalizedString('faqMoodleQ4'));?></h3>
    <p><?php echo(getLocalizedString('faqMoodleA4'));?></p>
    <h3 id="Moodle5"><?php echo(getLocalizedString('faqMoodleQ5'));?></h3>
    <p><?php echo(getLocalizedString('faqMoodleA5'));?></p>
   <h2>Windows Live Services</h2>
    <h3 id="WLS1"><?php echo(getLocalizedString('faqWLSQ1'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA1'));?></p>
    <h3 id="WLS2"><?php echo(getLocalizedString('faqWLSQ2'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA2'));?></p>
    <h3 id="WLS3"><?php echo(getLocalizedString('faqWLSQ3'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA3'));?></p>
    <h3 id="WLS4"><?php echo(getLocalizedString('faqWLSQ4'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA4'));?></p>
    <h3 id="WLS5"><?php echo(getLocalizedString('faqWLSQ5'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA5'));?></p>
    <h3 id="WLS6"><?php echo(getLocalizedString('faqWLSQ6'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA6'));?></p>
    <h3 id="WLS7"><?php echo(getLocalizedString('faqWLSQ7'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA7'));?></p>
    <h3 id="WLS8"><?php echo(getLocalizedString('faqWLSQ8'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA8'));?></p>
    <h3 id="WLS9"><?php echo(getLocalizedString('faqWLSQ9'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA9'));?></p>
    <h3 id="WLS10"><?php echo(getLocalizedString('faqWLSQ10'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA10'));?></p>
    <h3 id="WLS11"><?php echo(getLocalizedString('faqWLSQ11'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA11'));?></p>
    <h3 id="WLS12"><?php echo(getLocalizedString('faqWLSQ12'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA12'));?></p>
    <h3 id="WLS13"><?php echo(getLocalizedString('faqWLSQ13'));?></h3>
    <p><?php echo(getLocalizedString('faqWLSA13'));?></p>
    <hr>
    <p style="font-size:0.8em;color:#333333"><?php echo(getLocalizedString('faqFooter'));?></p>
</body>
</html>
