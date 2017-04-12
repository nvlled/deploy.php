# deploy.php

Easily deploy on a free hosting site with less troubles (or not).

## Usage

1. Upload deploy.php to your webhost or server.
2. Create a file ```.deploy-pass``` in the same directory. 
   The contents must be sha1 of your password 
   (for instance, sha1 of abcd is 81fe8bfe87576c3ecb22426f8e57847382917acf) 
3. Deploy either by opening the script in the browser (such as http://example.com/deploy.php)
   or by using command-line programs such as curl.

