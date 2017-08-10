## WP CLI command to replicate EasyEngine

### Installation method
* Install [WP CLI](https://make.wordpress.org/cli/handbook/installing/).
* Install this command as WP CLI package using the following command.
```bash
wp package install https://github.com/bnap00/wp-ee-command.git  
```
* That's it, No other steps.

### Commands.
* wp ee site create (Command to create new site)
* wp ee site list   (List all created sites)
* wp ee site update (Update a site)
* wp ee site delete (Delete a site)
* wp ee site info   (Show information of a particular site)

_You can check about the options and flag that you can pass using `--help` with any command specified above_

### Note
_This package emulates EasyEngine and does not create actual sites._

