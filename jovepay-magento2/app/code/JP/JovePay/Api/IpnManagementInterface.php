<?php 
namespace JP\JovePay\Api;
 
 
interface IpnManagementInterface {


	/**
	 * @param string $param
	 * @return string
	 */
	
	public function getPost($param = null);
}
