<?php
/**
 * @package     FOF
 * @copyright   2010-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace FOF30\Tests\Download\Adapter;

class FopenDataprovider
{
	public static function getTestDownloadAndReturn()
	{
		return array(
			array(
				'setup' => array(
					'httpstatus'	=> 200,
				),
				'test' => array(
					'url'		=> 'http://www.example.com/donwload.dat',
					'from'		=> 0,
					'to'		=> 0,
					'retSize' 	=> 1048576,
					'exception'	=> false,
					'message' 	=> 'Download a simple 1M file'
				)
			),

			array(
				'setup' => array(
				),
				'test' => array(
					'url'		=> 'http://www.example.com/IDoNotExist.dat',
					'from'		=> 0,
					'to'		=> 0,
					'retSize' 	=> 0,
					'exception'	=> array(
						'name'		=> 'Exception',
						'message'	=> 'LIB_FOF_DOWNLOAD_ERR_FOPEN_ERROR',
						'code'		=> '404'
					),
					'message' 	=> '404 on non-existent file results in Exception'
				)
			),

			array(
				'setup' => array(
					'httpstatus'	=> 403,
				),
				'test' => array(
					'url'		=> 'http://www.example.com/donwload.dat',
					'from'		=> 0,
					'to'		=> 0,
					'retSize' 	=> 0,
					'exception'	=> array(
						'name'		=> 'Exception',
						'message'	=> 'LIB_FOF_DOWNLOAD_ERR_HTTPERROR',
						'code'		=> '403'
					),
					'message' 	=> '403 Forbidden results in Exception'
				)
			),

			array(
				'setup' => array(
					'returnSize'	=> 2 * 1048576,
					'httpstatus'	=> 200,
				),
				'test' => array(
					'url'		=> 'http://www.example.com/donwload.dat',
					'from'		=> 0,
					'to'		=> 1048575,
					'retSize' 	=> 1048576,
					'exception'	=> false,
					'message' 	=> 'First 1M chunk of a 2M file'
				)
			),

			array(
				'setup' => array(
					'returnSize'	=> 2 * 1048576,
					'httpstatus'	=> 200,
				),
				'test' => array(
					'url'		=> 'http://www.example.com/donwload.dat',
					'from'		=> 1048576,
					'to'		=> 2 * 1048576 - 1,
					'retSize' 	=> 1048576,
					'exception'	=> false,
					'message' 	=> 'Last 1M chunk of a 2M file'
				)
			),

			array(
				'setup' => array(
					'returnSize'	=> 2 * 1048576,
					'httpstatus'	=> 200,
				),
				'test' => array(
					'url'		=> 'http://www.example.com/donwload.dat',
					'from'		=> 2 * 1048576 - 1,
					'to'		=> 1048576,
					'retSize' 	=> 1048576,
					'exception'	=> false,
					'message' 	=> 'Last 1M chunk of a 2M file, accidentally inverted to/from'
				)
			),
		);
	}
}