<?php
/**
 * @package     FOF
 * @copyright   2010-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace FOF30\Utils\InstallScript;

use Exception;
use FOF30\Database\Installer;
use JFactory;
use JLoader;

defined('_JEXEC') or die;

JLoader::import('joomla.filesystem.folder');
JLoader::import('joomla.filesystem.file');
JLoader::import('joomla.installer.installer');
JLoader::import('joomla.utilities.date');

// In case FOF's autoloader is not present yet, e.g. new installation
if (!class_exists('FOF30\\Utils\\InstallScript\\BaseInstaller', true))
{
	require_once __DIR__ . '/BaseInstaller.php';
}

/**
 * A helper class which you can use to create plugin installation scripts.
 *
 * Example usage: class Mod_ExampleInstallerScript extends FOF30\Utils\InstallScript\Module
 *
 * This namespace contains more classes for creating installation scripts for other kinds of Joomla! extensions as well.
 * Do keep in mind that only components, modules and plugins could have post-installation scripts before Joomla! 3.3.
 */
class Plugin extends BaseInstaller
{
	/**
	 * The plugins's name, e.g. foobar (for plg_system_foobar)
	 *
	 * @var   string
	 */
	protected $pluginName = 'mod_foobar';

	/**
	 * The plugins's folder, e.g. system (for plg_system_foobar)
	 *
	 * @var   string
	 */
	protected $pluginFolder = 'site';

	/**
	 * The path where the schema XML files are stored. The path is relative to the folder which contains the extension's
	 * files.
	 *
	 * @var string
	 */
	protected $schemaXmlPath = 'sql/xml';

	/**
	 * Joomla! pre-flight event. This runs before Joomla! installs or updates the component. This is our last chance to
	 * tell Joomla! if it should abort the installation.
	 *
	 * @param   string                      $type   Installation type (install, update, discover_install)
	 * @param   \JInstallerAdapterComponent $parent Parent object
	 *
	 * @return  boolean  True to let the installation proceed, false to halt the installation
	 */
	public function preflight($type, $parent)
	{
		// Check the minimum PHP version
		if (!$this->checkPHPVersion())
		{
			return false;
		}

		// Check the minimum Joomla! version
		if (!$this->checkJoomlaVersion())
		{
			return false;
		}

		// Clear op-code caches to prevent any cached code issues
		$this->clearOpcodeCaches();

		return true;
	}

	/**
	 * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
	 * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
	 * database updates and similar housekeeping functions.
	 *
	 * @param   string                      $type   install, update or discover_update
	 * @param   \JInstallerAdapterComponent $parent Parent object
	 *
     * @throws Exception
	 *
	 * @return  void
	 */
	public function postflight($type, $parent)
	{
		// Add ourselves to the list of extensions depending on FOF30
		$this->addDependency('fof30', $this->getDependencyName());

		// Install or update database
		$schemaPath  = $parent->getParent()->getPath('source') . '/' . $this->schemaXmlPath;

		if (@is_dir($schemaPath))
		{
			$dbInstaller = new Installer(JFactory::getDbo(), $schemaPath);
			$dbInstaller->updateSchema();
		}

		// Make sure everything is copied properly
		$this->bugfixFilesNotCopiedOnUpdate($parent);

		// Add post-installation messages on Joomla! 3.2 and later
		$this->_applyPostInstallationMessages();

		// Clear the opcode caches again - in case someone accessed the extension while the files were being upgraded.
		$this->clearOpcodeCaches();
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   \JInstallerAdapterComponent $parent The parent object
	 */
	public function uninstall($parent)
	{
		// Uninstall database
		$schemaPath  = $parent->getParent()->getPath('source') . '/' . $this->schemaXmlPath;

		// Uninstall database
		if (@is_dir($schemaPath))
		{
			$dbInstaller = new Installer(JFactory::getDbo(), $schemaPath);
			$dbInstaller->removeSchema();
		}

		// Uninstall post-installation messages on Joomla! 3.2 and later
		$this->uninstallPostInstallationMessages();

		// Remove ourselves from the list of extensions depending on FOF30
		$this->removeDependency('fof30', $this->getDependencyName());
	}

	/**
	 * Fix for Joomla bug: sometimes files are not copied on update.
	 *
	 * We have observed that ever since Joomla! 1.5.5, when Joomla! is performing an extension update some files /
	 * folders are not copied properly. This seems to be a bit random and seems to be more likely to happen the more
	 * added / modified files and folders you have. We are trying to work around it by retrying the copy operation
	 * ourselves WITHOUT going through the manifest, based entirely on the conventions we follow for Akeeba Ltd's
	 * extensions.
	 *
	 * @param   \JInstallerAdapterComponent $parent
	 */
	protected function bugfixFilesNotCopiedOnUpdate($parent)
	{
		$temporarySource = $parent->getParent()->getPath('source');

		$copyMap = array(
			// Plugin files
			$temporarySource            => JPATH_ROOT . '/plugins/' . $this->pluginFolder . '/' . $this->pluginName,
			// Language (always stored in administrator for plugins)
			'language/backend'          => JPATH_ADMINISTRATOR . '/language',
			// Media files, e.g. /media/plg_system_foobar
			$temporarySource . '/media' => JPATH_ROOT . '/media/' . $this->getDependencyName(),
		);

		foreach ($copyMap as $source => $target)
		{
			$this->recursiveConditionalCopy($source, $target);
		}
	}

	/**
	 * Get the extension name for FOF dependency tracking, e.g. plg_system_foobar
	 *
	 * @return  string
	 */
	protected function getDependencyName()
	{
		return 'plg_' . strtolower($this->pluginFolder) . '_' . $this->pluginName;
	}
}