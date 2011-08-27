<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2011 
 * @package    zipdown 
 * @license    GNU/LGPL 
 * @filesource
 */


/**
 * Class zipdown 
 *
 * @copyright  MEN AT WORK 2011 
 * @package    zipdown
 */
 
class zipdown extends Controller
{
	protected $strTemplate = 'templates/show_zipdown';
	protected $zip;
	
	function zipDownload($fileNames)
	{
		$tmpZippath = 'system/tmp/';
		$archiveFileName = 'download_'.rand(10000,99999).time().'.zip';
		$this->zip = new ZipWriter($tmpZippath.$archiveFileName);
		
		if (is_array($fileNames))
		{
			//add each file of $fileNames array to archive
			foreach($fileNames as $files)
			{
				$this->addFileToZip($files);
			}
		}
		else
		{
			//add file of $fileNames string to archive
			$this->addFileToZip($fileNames);
		}
		
		$this->zip->close();
		
		$content = file_get_contents($tmpZippath.$archiveFileName);
		unlink($tmpZippath.$archiveFileName);
		$temp = tmpfile();
		fwrite($temp,$content);
		rewind($temp);
		
		ob_get_clean();
		header('Content-Type: application/zip');
        header('Content-Transfer-Encoding: binary');
        header('Content-Disposition: attachment; filename="' . $archiveFileName . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
		fpassthru($temp);

		fclose($temp);
		
		// HOOK: post download callback
		if (isset($GLOBALS['TL_HOOKS']['postDownload']) && is_array($GLOBALS['TL_HOOKS']['postDownload']))
		{
			foreach ($GLOBALS['TL_HOOKS']['postDownload'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($strFile);
			}
		}
		exit;
	}	
	
	protected function addFileToZip($file)
	{
		if (file_exists(TL_ROOT . '/' . $file))
		{
			// extract filename
			$strName = substr($file,strripos($file,'/')+1);
			$this->zip->addString(file_get_contents(TL_ROOT . '/' . $file), ($strName ? $strName : $file), filemtime(TL_ROOT . '/' . $file));
		}
	}
	
	function zipdownForm($linkId, $dataArray, $linkString = 'Download')
	{	
		if ($this->Input->get('zipdown', true) == $linkId )
		{
			$this->zipDownload($dataArray); 
		}
		$this->Template = new FrontendTemplate($this->strTemplate);
		$this->Template->link = $linkString;
		$this->Template->linkId = $linkId;
		$this->Template->href = $this->Environment->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || strpos($this->Environment->request, '?') !== false) ? '&amp;' : '?') . 'zipdown=' . $linkId;

		return $this->Template->parse();
	}
}
?>