##Language support on Raspberry Pi

### 1) Check installed languages

You will need to have ssh access to be able to check the languages installed. 

From the cmd line enter the following:

    locale -a
    
This will result in a list showing the installed languages in the following format:

    C
    C.UTF-8
    en_GB.utf8
    POSIX

If you can't see your language in the list then you will need to install it by following the next step.

### 2) Install additional languages
Languages can only be installed using the built in configuration tool, this is where you will also be able to change your 
keyboard as well if required.

    sudo raspi-config

Follow the prompts on screen, and you will be able to install the required languages, if you check again for the languages
installed hopefully you will be able to see them in the list. Below you can see I have added 4 extra languages to the 
raspberry Pi.

    C
    C.UTF-8
    cy_GB.utf8
    da_DK.utf8
    en_GB.utf8
    es_ES.utf8
    fr_FR.utf8
    POSIX

### 3) EmonCMS logic for language selection

The CMS will use the following logic in order to display the correct language as long as the correct language is installed
on the Raspberry Pi.

ORDER OF PREFERENCE WITH LANGUAGE SELECTION
 1. non logged in users use the browser's language
 2. logged in users use their saved language preference
 3. logged in users without language saved uses `$default_language` from settings.ini
 4. else fallback is set to 'en_GB'

