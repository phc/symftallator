<?php
/**
 * Setup a local environment
 *
 * Started with code stolen from an old project
 *
 * @author Ludovic Vigouroux <ludo@mundoludo.fr>
 * @since  2012-03-03
 */
class createConfigTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('path', null, sfCommandOption::PARAMETER_REQUIRED, 'path to properties', 'properties.yml'),
      new sfCommandOption('ext', null, sfCommandOption::PARAMETER_REQUIRED, 'file extension for sample config files', '-sample'),
    ));

    $this->namespace = 'project';
    $this->name = 'create-config';
    $this->briefDescription = 'Create config files for local environnement';

    $this->detailedDescription = <<<EOF
The [project:create_config|INFO] task launch the creation of config files:

  [./symfony project:create-config |INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $pathKeyFile = $options['path'];
    $ext = $options['ext'];

    // Retrieve properties
    $this->logSection("Init", '');
    $this->log(sprintf("  Loading file key : %s ", $pathKeyFile));

    $config = file_exists($pathKeyFile) ? sfYaml::load($pathKeyFile) : null;

    // Failed to load properties
    if (is_null($config))
    {
      $this->log(sprintf("key file not found -> %s", $pathKeyFile));
      return true;
    }

    // for all files in properties file
    foreach ($config as $name => $fileToConfig)
    {
      $this->fileConfigs = $fileToConfig;

      if (is_array($this->fileConfigs))
      {
        // convert properties for this file in yaml
        $config = $this->createConfigArray();
      }
      else
      {
        $config = array();
      }

      // eventually merge with default value
      $distFile = $name.$ext;
      $this->fileConfigs = file_exists($distFile) ? (array) sfYaml::load($distFile) : null;
      if (null !== $this->fileConfigs)
      {
        $this->log(sprintf("Merge with default file -> %s ", $distFile));
        $dist = $this->createConfigArray();
        $config = $this->mergeArray($dist, $config);
      }

      // and save to yml file !
      $this->log(sprintf("Creating config file -> %s ", $name));
      file_put_contents($name, sfYaml::dump($config, 10));
    }
  }

  protected function mergeArray($sample, $config)
  {
    foreach ($config as $key => $value)
    {
      if (array_key_exists($key, $sample) && is_array($value))
      {
        $sample[$key] = $this->mergeArray($sample[$key], $config[$key]);
      }
      else
      {
        $sample[$key] = $value;
      }
    }

    return $sample;
  }

  protected function createConfigArray()
  {
    $config = array();
    foreach ($this->fileConfigs as $key => $value)
    {
      $this->fileKeys = $key;
      $pathKey = explode("/",$key);
      $config_retrieved = $this->traverseConfig($pathKey);
      $config = array_merge_recursive($config, $config_retrieved);
    }

    return $config;
  }

  protected function traverseConfig($config)
  {
    $root = array_shift($config);
    if (count($config) > 0)
    {
      $array[$root] = $this->traverseConfig($config);
    }
    else
    {
      $array[$root] = $this->fileConfigs[$this->fileKeys];
    }

    return $array;
  }
}
