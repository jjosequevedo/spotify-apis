# Drupal Challenge

This is a sample vanilla Drupal 8 installation pre-configured for use with Docksal.

Features:

- Vanilla Drupal 8
- `fin init` [example](.docksal/commands/init)
- Using the [default](.docksal/docksal.env#L9) Docksal LAMP stack with [image version pinning](.docksal/docksal.env#L13-L15)
- PHP and MySQL settings overrides [examples](.docksal/etc)
- Drush aliases [example](drush/aliases.drushrc.php) (`drush @docksal status`)

## Setup instructions

### Step #1: Docksal environment setup

**This is a one time setup - skip this if you already have a working Docksal environment.**  

Follow [Docksal environment setup instructions](https://docs.docksal.io/getting-started/setup/)

### Step #2: Project setup

1. Clone this repo into your Projects directory

    ```
    git clone https://git.jobsity.com/jobsity/drupal-challenge challenge-drupal
    cd challenge-drupal
    ```

2. Initialize the site

    This will initialize local settings and install the site via drush

    ```
    fin init
    ```

3. Point your browser to

    ```
    http://challenge-drupal.docksal
    ```

When the automated install is complete the command line output will display the admin username and password.

---

### Migration
- No DB Dump

### URLs

- [Spotify API] (https://developer.spotify.com/documentation/web-api/)
- [Endpoint] (https://api.spotify.com/v1/) 