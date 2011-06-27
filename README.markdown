
# FlamingGrowl - PHP CodeIgniter GNTP library

FlamingGrowl is a GNTP (Growl Notification Transport Protocol) library for CodeIgniter. It is designed to communicate with [Growl for Windows](http://www.growlforwindows.com/) by 
sending notifications from your CodeIgniter application to a desired computer.

# Requirements

* PHP 5
* CodeIgniter Reactor 2.0.2 and above
* [Growl for Windows](http://www.growlforwindows.com/) installed on your computer

# Installation

1. Download FlamingGrowl
2. Copy the files to your application folder
3. Set up flaming_growl.php configuration file to meet your needs
4. Make sure that you have [Growl for Windows](http://www.growlforwindows.com/) installed and the specified port open on your computer (default port is 23053)
5. Don't forget to adjust Security settings in [Growl for Windows](http://www.growlforwindows.com/) (allow notifications from websites, add at least one password).

# Usage

To begin using FlamingGrowl in your CodeIgniter application, you need to load the library first.

`$this->load->library('FlamingGrowl');`

This will also load the corresponding configuration file, so you don't need to do it manually. 

If you find using the **FlamingGrowl** handle awkward, you can specify the object name for your convenience:

`$this->load->library('FlamingGrowl', NULL, 'growl');`

and call library methods like this:

`$this->growl->some_method();`

## Registering an application within Growl for Windows

It is important to register an application before sending any notifications. Otherwise you'll get
a bad response from Growl. However, you only need to do it when you are registering a new application or additional notification types. Registering an application actually means sending a **REGISTER** GNTP request.

`$this->growl->register();`

This will register an application with default settings from the configuration file. Usually you don't need to use more than one application name, but you can pass in an array with different settings.

```
$this->growl->register(
array
(
    'application_name'      =>	'My Application',
    'host'                  =>	'localhost',
    'port'                  =>	23053,
    'timeout'               =>	10,
    'password'              =>  'my_password'
));
```

You'll find all available options in the configuration file. Note that you **need** to specify a password (either in the configuration file, or when sending a request) if you are going to send
notifications from another computer (which will mostly be the case since you usually store your 
web scripts on a server rather than your own machine).

## Sending notifications

If you have successfully registered an application, you can start sending NOTIFY requests.

`$this->growl->notify($name, $title, $text, $options);`

* **$name** specifies the ID name (not to be confused with *Notification-ID* if you are also reading the GNTP documentation) of the notification (notification type).

* **$title** - notification display name, which will be shown when the notification appears.

* **$text** - a short description of the notification.

* **$options** - by default FlamingGrowl will use settings from the config file and the array specified when registering an application, but you can also set notification related options here.

### Example

```
$this->growl->register(
array
(
    'application_name'      =>  'System messages',
    'notifications'         =>  array(
                                      array(
                                            'name'          =>  'ERROR',
                                            'display'       =>  'Error',
                                            'enabled'       =>  true,
                                            'icon'          =>  URL_TO_ICON
                                            ),
                                      array(
                                            'name'          =>  'UPDATE',
                                            'display'       =>  'Update',
                                            'enabled'       =>  true,
                                            'icon'          =>  URL_TO_ICON
                                            )
                                        )
)
);

$this->growl->notify('ERROR', 'SQL database backup failed', 'The CMS could not backup the SQL database.');

$options = array('sticky' => false, 'icon' => 'http://website.com/icon.png');

$this->growl->notify('UPDATE', 'New user', 'New user has registered on the website', $options);
```

## SUBSCRIBE requests

`$this->growl->subscribe($id, $name, $port);`

* **$id** - A unique id (UUID) that identifies the subscriber
* **$name** - The friendly name of the subscribing machine
* **$port** - (Optional) port that the subscriber will listen for notifications on (default 23053)

In order to get an OK response after sending a SUBSCRIBE request, make sure Growl allows subscribtions (Growl for Windows: 'Allow clients to subscribe to notifications').

### Example

```
$unique_id = '0f8e3530-7a29-11df-93f2-0800200c9a66';

$this->growl->subscribe($unique_id, 'FlamingGrowl Subscriber');
```