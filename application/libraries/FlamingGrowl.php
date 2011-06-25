<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * GNTP Class
 *
 * FlamingGrowl is a GNTP (Growl Notification Transport Protocol) library 
 * for CodeIgniter. It is designed to communicate with Growl for Windows 
 * by sending notifications from your CodeIgniter application to a desired 
 * computer.
 *
 * GNTP reference: http://www.growlforwindows.com/gfw/help/gntp.aspx
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Edmundas Kondrašovas <as@edmundask.lt>
 * @link		http://github.com/edmundask/FlamingGrowl
 * @version		0.7
 *
 */

class FlamingGrowl
{
	protected $CI;

	// Valid GNTP hashing algorithms
	private $_hashing_algorithms = array('md5', 'sha1', 'sha256', 'sha512');

	private $_response;

	/**
	* Constructor
	*/

	public function __construct()
	{
		$this->CI =& get_instance();

		$this->CI->load->config('flaming_growl');
	}

	/**
	* Register an application
	*
	* @access	public
	* @param	array REGISTER request headers and GNTP options
	* @return	void
	*/

	public function register($options = array())
	{
		// Initialize options if any are set
		if(is_array($options) && count($options) > 0)
		{
			$reg_options 	= $this->CI->config->item('register');
			$gntp_options 	= $this->CI->config->item('gntp');

			foreach($options as $key => $value)
			{
				if(array_key_exists($key, $reg_options) || $key == 'notifications')
				{
					$reg_options[$key] = $value;
				}
				elseif(array_key_exists($key, $gntp_options))
				{
					$gntp_options[$key] = $value;
				}
			}

			// Update REGISTER request headers
			$this->CI->config->set_item('register', $reg_options);

			// Update global GNTP options
			$this->CI->config->set_item('gntp', $gntp_options);
		}

		$data = $this->CI->config->item('register');
		if(!array_key_exists('notifications', $data))
		{
			$data['notifications'] = array(
				array(
						'name'		=>	$data['notification_name'],
						'display'	=>	$data['notification_display'],
						'enabled'	=>	$data['notification_enabled'],
						'icon'		=>	$data['notification_icon']
					 )
			);
		}

		$request = $this->_form_request('register', $data);

		$resource = $this->_connect($this->CI->config->item('host', 'gntp'),
									$this->CI->config->item('port', 'gntp'),
									$this->CI->config->item('timeout', 'gntp'));

		if($resource)
		{
			$this->_send_request($resource, $request);

			$this->_catch_response($resource);
			fclose($resource);
		}
			
	}

	/**
	* Send a NOTIFY request
	*
	* @access	public
	* @param	string notification name
	* @param	string the title to display for the notification
	* @param	string 
	* @param	array NOTIFY request headers and GNTP options
	* @return	void
	*/

	public function notify($name, $title, $text = '', $options = array())
	{
		$defined_options = $this->CI->config->item('notify');

		// Initialize options if any are set
		if(is_array($options) && count($options) > 0)
		{
			foreach($options as $key => $value)
			{
				if(array_key_exists($key, $defined_options))
				{
					$defined_options[$key] = $value;
				}
			}
		}

		$defined_options['application_name'] = $this->CI->config->item('application_name', 'register');
		$defined_options['name']	= $name;
		$defined_options['title']	= $title;
		$defined_options['text']	= $text;

		$request = $this->_form_request('notify', $defined_options);

		$resource = $this->_connect($this->CI->config->item('host', 'gntp'),
									$this->CI->config->item('port', 'gntp'),
									$this->CI->config->item('timeout', 'gntp'));

		if($resource)
		{
			$this->_send_request($resource, $request);
			$this->_catch_response($resource);
			fclose($resource);
		}
	}

	/**
	* Initiate a new socket connection
	*
	* @access	private
	* @param	string hostname
	* @param	int port number
	* @param	int socket connection timeout (in seconds) 
	* @return	resource/bool
	*/

	private function _connect($host, $port, $timeout)
	{
		return fsockopen($host, $port, $errno, $errstr, $timeout);
	}

	/**
	* Form a request
	*
	* Sets all required headers based on the request type before
	* sending the request.
	*
	* @access	private
	* @param	resource a valid pointer to an established resource
	* @return	string prepared request
	*/

	private function _form_request($type, $options)
	{
		if( ($type != 'register') && ($type != 'notify') && ($type != 'subscribe') ) return '';

		$hash		= $this->_hash_password($this->CI->config->item('password', 'gntp'));
		$encryption	= 'NONE';	// Don't worry, data encryption is added to my TO-DO list!

		$headers	=	'';

		switch($type)
		{
			case 'register':

				$headers	.=	"GNTP/". $this->CI->config->item('gntp_version') ." REGISTER $encryption $hash \r\n";

				$headers	.=	"Application-Name: ". $options['application_name'] ." \r\n";

				if(!empty($options['application_icon']))
					$headers .= "Application-Icon: ". $options['application_icon'] ." \r\n";

				$headers	.=	"Notifications-Count: ". count($options['notifications']) ." \r\n";

				$headers	.=	"\r\n";

				foreach($options['notifications'] as $notification)
				{
					$headers	.=	"Notification-Name: ". $notification['name'] ." \r\n";

					if(!empty($notification['display']))
						$headers .= "Notification-Display-Name: ". $notification['display'] ." \r\n";

					$headers .= "Notification-Enabled: ". $this->_toBool($notification['enabled']) ." \r\n";

					if(!empty($notification['icon']))
						$headers .= "Notification-Icon: ". $notification['icon'] ." \r\n";

					$headers	.=	"\r\n";
				}

				$headers	.=	"\r\n \r\n";

			break;

			case 'notify':

				$headers	.=	"GNTP/". $this->CI->config->item('gntp_version') ." NOTIFY $encryption $hash \r\n";

				echo $options['application_name'];
				echo '<br>';

				$headers	.=	"Application-Name: ". $options['application_name'] ." \r\n";
				$headers	.=	"Notification-Name: ". $options['name'] ." \r\n";

				if(!empty($options['id']))
					$headers .= "Notification-ID: ". $options['id'] ." \r\n";

				$headers	.=	"Notification-Title: ". $options['title'] ." \r\n";

				if(!empty($options['text']))
					$headers .= "Notification-Text: ". $options['text'] ." \r\n";

				$headers	.=	"Notification-Sticky: ". $this->_toBool($options['sticky']) ." \r\n";
				$headers	.=	"Notification-Priority: ". $options['priority'] ." \r\n";

				if(!empty($options['icon']))
					$headers .= "Notification-Icon: ". $options['icon'] ." \r\n";

				if(!empty($options['coalescing_id']))
					$headers .= "Notification-Coalescing-ID: ". $options['coalescing_id'] ." \r\n";

				if(!empty($options['coalescing_id']))
					$headers .= "Notification-Callback-Context: ". $options['callback_context'] ." \r\n";

				if(!empty($options['callback_context_type']))
					$headers .= "Notification-Callback-Context-Type: ". $options['callback_context_type'] ." \r\n";

				if(!empty($options['callback_target']))
					$headers .= "Notification-Callback-Target: ". $options['callback_target'] ." \r\n";

				$headers	.=	"\r\n \r\n";

			break;

			// No support for SUBSCRIBE requests yet...
			case 'subscribe':
			break;
		}

		return $headers;
	}

	/**
	* Generate necessary hashes for authentication
	*
	* @access	private
	* @param	string a password which needs to be hashed
	* @return	string required hash data for the header
	*/

	private function _hash_password($password = '')
	{
		$method = strtolower($this->CI->config->item('hash_method'));

		if(!in_array($method, $this->_hashing_algorithms)) return '';

		$this->CI->load->helper('str_hex');

		$salt		=	(string) mt_rand(39543312, mt_getrandmax());

		$key_hex	=	strToHex($password);
		$salt_hex	=	strToHex($salt);

		$key_bytes	=	pack("H*", $key_hex);
		$salt_bytes	=	pack("H*", $salt_hex);

		$key_basis 	=	$key_bytes . $salt_bytes;
		$key 		=	hash($method, $key_basis, true);
		$key_hash 	=	hash($method, $key);

		$hash		=	strtoupper("$method:$key_hash.$salt_hex");

		return $hash;
	}

	/**
	* Send request to Growl
	*
	* @access	private
	* @param	resource a valid pointer to an established resource
	* @param	string properly formed request
	* @return	bool
	*/

	private function _send_request($resource, $request)
	{
		fwrite($resource, $request);
	}

	/**
	* Catch the latest response from Growl
	*
	* @access	private
	* @param	resource a valid pointer to an established resource
	* @return	void
	*/

	private function _catch_response($resource)
	{
		$this->_response = '';

		while(($response = fgets($resource)) != false)
		{
			$this->_response .= $response .' <br>';
		}
	}

	/**
	* Get the latest response (full)
	*
	* @access	public
	* @return	string response of the latest request
	*/

	public function get_response()
	{
		return $this->_response;
	}

	/**
	* Status of the latest response
	*
	* Could be used for a quick check if the latest request 
	* was successful.
	*
	* @access	public
	* @return	bool was it a successful request?
	*/

	public function response()
	{
		return true;
	}

    private function _toBool($value)
    {
        if (preg_match('/^([Tt]rue|[Yy]es)$/', $value)) {
            return 'True';
        }
        if (preg_match('/^([Ff]alse|[Nn]o)$/', $value)) {
            return 'False';
        }
        if ((bool)$value === true) {
            return 'True';
        }
        return 'False';
    }

}
// End Class

/* End of file FlamingGrowl.php */
/* Location: ./application/libraries/FlamingGrowl.php */