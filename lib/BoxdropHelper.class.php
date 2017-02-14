<?php
	/**
	 * NOTICE OF LICENSE
	 *
	 * This source file is subject to the Academic Free License (AFL 3.0)
	 * that is bundled with this package in the file LICENSE.txt.
	 * It is also available through the world-wide-web at this URL:
	 * http://opensource.org/licenses/afl-3.0.php
	 * If you did not receive a copy of the license and are unable to
	 * obtain it through the world-wide-web, please send an email
	 * to license@prestashop.com so we can send you a copy immediately.
	 *
	 * @author    boxdrop Group AG
	 * @copyright boxdrop Group AG
	 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
	 * International Registered Trademark & Property of boxdrop Group AG
	 */

	/**
	 * Helpers
	 *
	 * @author  sweber <sw@boxdrop.com>
	 * @package BoxdropShipment
	 */
	abstract class BoxdropHelper
	{
		const ENV_BACKEND = 'BackOffice';
		const ENV_FRONTEND = 'FrontOffice';
		/**
		 * Tries to find the admin folder, as the name has a random suffix.
		 * As there are configurations out there with admin folders without any "admin" in fodler name,
		 * we'll check the index.php file inside each main subfolder fo the the string
		 * "define('_PS_ADMIN_DIR_', getcwd()"
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return null|string
		 */
		public static function getAdminDirPath()
		{
			$admin_dir = null;
			$root_dir = realpath(dirname(__FILE__).'/../../../').'/';
			if (!self::isCloudInstance())
			{
				$directories = scandir($root_dir);
				foreach ($directories as $dir_content)
				{
					if ($dir_content != '.' && $dir_content != '..')
					{
						$absolute_element = $root_dir.$dir_content;
						if (is_dir($absolute_element))
						{
							$index_file = $absolute_element.'/index.php';
							if (file_exists($index_file))
							{
								$file_contents = file_get_contents($index_file);
								if (strpos($file_contents, "define('_PS_ADMIN_DIR_', getcwd()") !== false)
								{
									$admin_dir = $absolute_element;
									break;
								}
							}
						}
					}
				}
			}
			else
			{
				list ($empty, $home, $user) = explode('/', $root_dir);
				$admin_dir = '/home/'.$user{0}.'/'.$user{1}.'/'.$user{2}.'/'.$user.'/www/backoffice/';
			}
			return $admin_dir;
		}


		/**
		 * Returns boolean wheter this installation is a PrestaShop cloud one
		 * Wrapped in here, because for AJAX calls we have to know this PRIOR to initialize the rest of the contents
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return boolean
		 */
		public static function isCloudInstance()
		{
			if (defined('_PS_HOST_MODE_'))
				return true;

			$root_dir = realpath(dirname(__FILE__).'/../../../').'/';

			if (!is_readable($root_dir.'webservice/') &&
			    !is_dir($root_dir.'webservice/'))
				return true;

			return false;
		}

		/**
		 * Returns the absolute path to our file-data directory.
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return string
		 */
		public static function getDataDir()
		{
			return realpath(dirname(__FILE__).'/../data/').'/';
		}

		/**
		 * Returns our carrier ID for the given mode
		 * Optionally sets a new value
		 *
		 * @author sweber  <sw@boxdrop.com>
		 * @param  string  $mode
		 * @param  integer $val
		 * @return integer
		 */
		public static function getCarrierId($mode = self::CONF_MODE_DIRECT_ECONOMY, $val = null)
		{
			if ($mode !== BoxdropShipment::CONF_MODE_DIRECT_ECONOMY && $mode !== BoxdropShipment::CONF_MODE_DIRECT_EXPRESS &&
			$mode !== BoxdropShipment::CONF_MODE_DROPOFF_ECONOMY && $mode !== BoxdropShipment::CONF_MODE_DROPOFF_EXPRESS)
				$mode = BoxdropShipment::CONF_MODE_DROPOFF;

			if ($val !== null)
				Configuration::updateValue($mode, $val);

			return Configuration::get($mode);
		}

		/**
		 * Returns all carrier IDs
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return array
		 */
		public static function getCarrierIds()
		{
			return array(
				BoxdropShipment::CONF_MODE_DIRECT_ECONOMY => self::getCarrierId(BoxdropShipment::CONF_MODE_DIRECT_ECONOMY),
				BoxdropShipment::CONF_MODE_DIRECT_EXPRESS => self::getCarrierId(BoxdropShipment::CONF_MODE_DIRECT_EXPRESS)
			);
		}

		/**
		 * Returns boolean whether a request for BoxdropAjaxRequest is targeted at the backend.
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return boolean
		 */
		public static function isBackendRequest()
		{
			$action_set = false;
			/*
			 * It may happen that class "Tools" is not yet loaded.
			 */
			if (class_exists('Tools'))
				$action_set = Tools::getIsset('action');
			else
			{
				/*
				 * to avoid forbidden isset() - calls, we have to choose another way...
				 */
				$get_vars = array_keys($_POST);
				foreach ($get_vars as $param_name)
				{
					if ($param_name == 'action')
						$action_set = true;

				}
			}
			if ($action_set)
				return (strpos($_POST['action'], 'adm') !== false);

			return false;
		}
		/**
		 * Return the default sender shipment address, which is defined in the main administration
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return array
		 */
		public static function getDefaultSenderAddress()
		{
			return array(
				'company' => Configuration::get('PS_SHOP_NAME'),
				'prename' => '',
				'name' => '',
				'title' => '',
				'street' => Configuration::get('PS_SHOP_ADDR1'),
				'street2' => Configuration::get('PS_SHOP_ADDR2'),
				'zip' => Configuration::get('PS_SHOP_CODE'),
				'city' => Configuration::get('PS_SHOP_CITY'),
				'telephone' => Configuration::get('PS_SHOP_PHONE'),
				'mail' => Configuration::get('PS_SHOP_EMAIL'),
				'country_code' => Configuration::get(BoxdropShipment::CONF_API_COUNTRY)
			);
		}

		/**
		 * Shortcut to get translation for our module text
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @see    Module::l()
		 * @param  string $string String to translate
		 * @return string Translation
		 */
		public static function l($string)
		{
			return Translate::getModuleTranslation('boxdropshipment', $string, 'boxdropshipment');
		}

		/**
		 * Checks if the given folder is existing and writeable
		 * Folder will be created if non-existing.
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @param  string $path
		 * @return void
		 */
		public static function checkAndCreateWriteable($path)
		{
			if (!is_writeable($path) || !is_dir($path))
			{
				$old_umask = umask();
				umask(0);
				mkdir($path, 0775, true);
				umask($old_umask);
			}
		}

		/**
		 * Returns an address array for boxdrop API usage with an orders receiver data
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @param  Order  $order
		 * @return array
		 */
		public static function getReceiverAddressByOrder($order)
		{
			$customer = new Customer($order->id_customer);
			$receiver_address = new Address($order->id_address_delivery);
			$receiver_country = new Country($receiver_address->id_country);
			return array(
				'company' => $receiver_address->company,
				'title' => (($customer->id_gender == 1) ? 1 : 2),
				'prename' => $receiver_address->firstname,
				'name' => $receiver_address->lastname,
				'street' => $receiver_address->address1,
				'street2' => $receiver_address->address2,
				'zip' => $receiver_address->postcode,
				'city' => $receiver_address->city,
				'mail' => $customer->email,
				'telephone' => $receiver_address->phone,
				'tax_number' => $receiver_address->vat_number,
				'country_code' => $receiver_country->iso_code
			);
		}

		/**
		 * Returns an initialized BoxdropSDK
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @return BoxdropSDK
		 */
		public static function getBoxdropSDK()
		{
			$sdk = BoxdropSDK::getInstance(Configuration::get(BoxdropShipment::CONF_API_USER_ID), Configuration::get(BoxdropShipment::CONF_API_PASS),
			Configuration::get(BoxdropShipment::CONF_API_HMAC_KEY));

			if (!$sdk->isInitalized())
			{
				$url_default = Configuration::get('PS_SHOP_DOMAIN');
				$url_ssl = Configuration::get('PS_SHOP_DOMAIN_SSL');
				$source_url = ($url_default == '') ? $url_ssl : $url_default;
				$source = 'PRESTASHOP - '.$source_url;
				$sdk->initConnection(Configuration::get(BoxdropShipment::CONF_API_COUNTRY), (boolean)Configuration::get(BoxdropShipment::CONF_API_TEST_MODE),
				$source);
			}
			return $sdk;
		}

		/**
		 * Returns an array out of the given string.
		 * Wrapped explode, to avoid creating an array with an empty value upon empty $input
		 *
		 * @author sweber <sw@boxdrop.com>
		 * @param  string $delimiter
		 * @param  string $input
		 * @return array
		 */
		public static function explodeString($delimiter, $input)
		{
			if (empty($input))
				return array();
			else
				return explode($delimiter, $input);
		}


	    /**
	     * Converts user entered form numbers into float values.
	     *
	     * @author sweber <sw@boxdrop.com>
	     * @param  mixed  $number
	     * @return float
	     */
	    public static function convertFormNumberToFloat($number)
		{
			return (float)str_replace(array('.', ','), array('','.'), $number);
		}
	}
