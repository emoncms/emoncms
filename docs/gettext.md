## Using gettext on Ubuntu

### 1) Create an example script

Create a folder on your server and copy and paste the following code into a php script inside the folder. For this example lets call the folder _example_ and the script _index.php_

    <?php
    // I18N support information here
    $language = "en_US";
    putenv("LANG=" . $language);
    setlocale(LC_ALL, $language);
    
    // Set the text domain as "messages"
    $domain = "messages";
    bindtextdomain($domain, "Locale");
    bind_textdomain_codeset($domain, 'UTF-8');
    
    textdomain($domain);
    
    echo _("HELLO_WORLD");
    ?>
    

If you open the script in your browser without gettext correctly set-up you should see "HELLO\_WORLD" printed to the screen.

### 2) Create the gettext translation files directory structure:

    var/www/example/Locale/en_US/LC_MESSAGES

### 3) Create your translation files using poedit

Poedit is a cross platform editior for gettext catalogues.

    $ sudo apt-get install poedit

Open poedit and go to File -\> New catalog. In the settings dialog box project info tab enter the following:

![](files/gettext/settings_info.png)

In the Paths tab enter the path to the root directory of the project, be sure to add the path in both boxes:

![](files/gettext/settings_path.png)

Save to messages.po inside the LC\_MESSAGES folder:

![](files/gettext/poedit_savemessages.png)

Poedit should then give you a summary of the strings to be translated:

![](files/gettext/poedit_summary.png)

Click OK and then create a test translation. Click on the string in the main window, it will then appear the second block below, in the third block enter in your translation. Here just as a test I have asked it to translate HELLO\_WORLD into testing translation..

![](files/gettext/poedit_testingtranslation.png)

Click save and then exit.

### 4) Install gettext

    $ sudo apt-get install gettext

### 5) Generate the locale for your language.

The supported locales can be found in '/usr/share/i18n/SUPPORTED'. Open the file to see the supported languages and character sets:

    $ sudo nano /usr/share/i18n/SUPPORTED

To change linux language you need to reconfigure the locales and pick from the list the UTF-8 for the desired language 

    $ sudo dpkg-reconfigure locales
    
You will see multiple lines for all the languages, assuming (as an example) you are translating to en\_US you should pick always the UTF-8 (8-bit Unicode Transformation Format):

    [X] en_US.UTF-8 UTF-8
    [ ] en_US ISO-8859-1
    [ ] en_US.ISO-8859-15 ISO-8859-15
    
To generate the locale for the default character set, run the following:

    $ sudo locale-gen en_US

### 6) Restart Apache

    $ sudo /etc/init.d/apache2 restart 
    
To enable a language as a default for emonCMS you need to edit `locale.php` line 88
    
    $ default  : $lang='en_US';

*Note: currently not all Emoncms strings have been translated*


This short guide is based on the following useful tutorials and QA:

[http://phpmaster.com/localizing-php-applications-1/][0]

[http://stackoverflow.com/questions/5257519/cant-get-gettext-php-on-ubuntu-working][1]


[0]: http://phpmaster.com/localizing-php-applications-1/
[1]: http://stackoverflow.com/questions/5257519/cant-get-gettext-php-on-ubuntu-working
