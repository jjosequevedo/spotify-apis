# Drupal Challenge

##  Setup instructions

### Step #1: Docksal environment setup
This is a one time setup - skip this if you already have a working Docksal environment.
Follow Docksal environment setup instructions

### Step #2: Project setup
Clone this repo into your Projects directory
git clone https://git.jobsity.com/jobsity/drupal-challenge challenge-drupal
cd challenge-drupal


### Initialize the site
This will initialize local settings and install the site via drush
fin init


### Point your browser to
http://challenge-drupal.docksal.site

When the automated install is complete the command line output will display the admin username and password. This happens before importing configuration.

### Step #3: Load data in your site
1. Go to Configuration > DC Spotify > Spotify settings
2. Insert credentials from spotify `client_id` and `client_secret`. (Check the information [App Settings](https://developer.spotify.com/documentation/general/guides/authorization/app-settings/))
3. Save it
4. Go to Configuration > DC Spotify > Load data
5. Click on the button `Load data` and wait until the message is displayed `All information was loaded successfully from Spotify.`

