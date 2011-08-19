<?

namespace okapi;

use Exception;

class OkapiServiceRunner
{
	#
	# This the list of all available OKAPI methods. All methods on this list become
	# immediately public and all of them have to be documented. It is not possible
	# to create an invisible or undocumented OKAPI method. If you want to test your
	# methods, you should do it in your local development server. If you want to
	# create a private, "internal" method, you still have to document it properly
	# (i.e. describe it as "internal" and accessible to selected consumer keys only).
	#
	public static $all_names = array(
		# Valid format: ^services/[0-9a-z_/]*$ (it means you may use only alphanumeric
		# characters and the "_" sign in your method names).
		'services/apisrv/installation', 
		'services/apiref/method',
		'services/apiref/method_index',
		'services/oauth/request_token',
		'services/oauth/authorize',
		'services/oauth/access_token',
		'services/caches/search/all',
		'services/caches/search/bbox',
		'services/caches/search/nearest',
		'services/caches/shortcuts/search_and_retrieve',
		'services/caches/geocache',
		'services/caches/geocaches',
		'services/users/user',
		'services/users/users',
		'services/users/by_usernames',
		'services/users/by_username'
	);
	
	/** Check if method exists. */
	public static function exists($service_name)
	{
		return in_array($service_name, self::$all_names);
	}
	
	/** Get method options (is consumer required etc.). */
	public static function options($service_name)
	{
		if (!self::exists($service_name))
			throw new Exception();
		require_once "$service_name.php";
		try
		{
			return call_user_func(array('\\okapi\\'.
				str_replace('/', '\\', $service_name).'\\WebService', 'options'));
		} catch (Exception $e)
		{
			throw new Exception("Make sure you've declared your WebService class ".
				"in an valid namespace (".'okapi\\'.str_replace('/', '\\', $service_name)."); ".
				$e->getMessage());
		}
	}
	
	/** 
	 * Get method documentation file contents (stuff within the XML file).
	 * If you're looking for a parsed representation, use services/apiref/method.
	 */
	public static function docs($service_name)
	{
		if (!self::exists($service_name))
			throw new Exception();
		try {
			return file_get_contents("$service_name.xml", true);
		} catch (Exception $e) {
			throw new Exception("Missing documentation file: $service_name.xml");
		}
	}
	
	/** 
	 * Execute the method and return the result.
	 * 
	 * OKAPI methods return OkapiHttpResponses, but some MAY also return
	 * PHP objects (see OkapiRequest::construct_inside_request for details).
	 * 
	 * If $request must be consistent with given method's options (must
	 * include Consumer and Token, if they are required).
	 */
	public static function call($service_name, OkapiRequest $request)
	{
		if (!self::exists($service_name))
			throw new Exception();
		
		$options = self::options($service_name);
		if ($options['consumer'] == 'required' && $request->consumer == null)
		{
			throw new Exception("Method '$service_name' called with mismatched OkapiRequest: ".
				"\$request->consumer MAY NOT be empty. Provide a dummy Consumer if you have to.");
		}
		if ($options['token'] == 'required' && $request->token == null)
		{
			throw new Exception("Method '$service_name' called with mismatched OkapiRequest: ".
				"\$request->token MAY NOT be empty.");
		}
		
		require_once "$service_name.php";
		return call_user_func(array('\\okapi\\'.
			str_replace('/', '\\', $service_name).'\\WebService', 'call'), $request);
	}
	
}