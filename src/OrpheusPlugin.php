<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\InstalledVersions;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Orpheus\Service\OrpheusPhpCompiler;

class OrpheusPlugin implements PluginInterface, EventSubscriberInterface {
	
	const EXTENSION_TYPE = 'orpheus-library';
	
	protected Composer $composer;
	
	protected IOInterface $io;
	
	public function activate(Composer $composer, IOInterface $io): void {
		$this->composer = $composer;
		$this->io = $io;
	}
	
	public function deactivate(Composer $composer, IOInterface $io) {
	
	}
	
	public function uninstall(Composer $composer, IOInterface $io) {
	
	}
	
	protected function getInstalledOrpheusLibraries(): array {
		$type = self::EXTENSION_TYPE;
		$packageNames = array_unique(InstalledVersions::getInstalledPackagesByType($type));
		$repositoryManager = $this->composer->getRepositoryManager();
		$packages = [];
		foreach( $packageNames as $packageName ) {
			$package = $repositoryManager->findPackage($packageName, '*');
			if( $package ) {
				$packages[] = $package;
			}
		}
		
		return $packages;
	}
	
	public function onPostUpdate(Event $event): void {
		$packages = $this->getInstalledOrpheusLibraries();
		$type = self::EXTENSION_TYPE;
		$extensions = [];
		foreach( $packages as $package ) {
			$packageExtra = $package?->getExtra();
			$packagePluginConfig = $packageExtra[$type];
			$extensionClass = $packagePluginConfig['class'] ?? null;
			if( $extensionClass ) {
				$extensions[] = $extensionClass;
			}
		}
		OrpheusPhpCompiler::initialize($this->getApplicationPath() . '/store/compiler');
		$compiler = OrpheusPhpCompiler::get();
		$compiler->compileArray('orpheus-libraries', $extensions);
	}
	
	protected function getApplicationPath(): string {
		return realpath($this->composer->getConfig()->get('vendor-dir') . '/..');
	}
	
	public static function getSubscribedEvents(): array {
		return [
			ScriptEvents::POST_UPDATE_CMD => 'onPostUpdate',
		];
	}
	
}
