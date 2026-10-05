<?php

use SpipLeague\EasyCodingStandard\Set\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
	->withSets([SetList::SPIP])
	->withPaths([__DIR__])
	->withRootFiles()
	->withSkip([__DIR__ . '/lib', __DIR__ . '/vendor'])
;
