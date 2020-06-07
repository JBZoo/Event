#
# JBZoo Toolbox - Event
#
# This file is part of the JBZoo Toolbox project.
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.
#
# @package    Event
# @license    MIT
# @copyright  Copyright (C) JBZoo.com, All rights reserved.
# @link       https://github.com/JBZoo/Event
#

ifneq (, $(wildcard ./vendor/jbzoo/codestyle/src/init.Makefile))
    include ./vendor/jbzoo/codestyle/src/init.Makefile
endif


update: ##@Project Install/Update all 3rd party dependencies
	$(call title,"Install/Update all 3rd party dependencies")
	@echo "Composer flags: $(JBZOO_COMPOSER_UPDATE_FLAGS)"
	@composer update $(JBZOO_COMPOSER_UPDATE_FLAGS)


test-performance: ##@Project Run performance tests
	$(call title,"Run benchmark tests")
	@php `pwd`/vendor/bin/phpbench run         \
        --tag=jbzoo                            \
        --store                                \
        --iterations=10                        \
        --warmup=2                             \
        --sleep=2000                           \
        --stop-on-error                        \
        -vvv
	$(call title,"Build reports - CLI")
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --mode=time                            \
        --precision=2                          \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --mode=throughput                      \
        --precision=2                          \
        -vvv
	$(call title,"Build reports - HTML")
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --output=jbzoo-html-time               \
        --mode=time                            \
        --precision=3                          \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --output=jbzoo-html-throughput         \
        --mode=throughput                      \
        --precision=3                          \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-env                     \
        --precision=3                          \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-env                     \
        --output=jbzoo-html-env                \
        --precision=3                          \
        -vvv


test-all: ##@Project Run all project tests at once
	@make test
	@make codestyle
	@make test-performance
