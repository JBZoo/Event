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

XDEBUG_OFF ?= no

ifneq (, $(wildcard ./vendor/jbzoo/codestyle/src/init.Makefile))
    include ./vendor/jbzoo/codestyle/src/init.Makefile
endif


update: ##@Project Install/Update all 3rd party dependencies
	$(call title,"Install/Update all 3rd party dependencies")
	@echo "Composer flags: $(JBZOO_COMPOSER_UPDATE_FLAGS)"
	@composer update $(JBZOO_COMPOSER_UPDATE_FLAGS)


test-all: ##@Project Run all project tests at once
	@make test
	@make codestyle
	@if [ $(XDEBUG_OFF) = "yes" ]; then  \
       make test-performance;            \
    else                                 \
      echo "Performance test works only if XDEBUG_OFF=yes"; \
    fi;


test-performance: ##@Project Run benchmarks and performance tests
	$(call title,"Run benchmark tests")
	@php `pwd`/vendor/bin/phpbench run         \
        --tag=jbzoo                            \
        --store                                \
        --warmup=2                             \
        --stop-on-error                        \
        -vvv
	$(call title,"Build reports - CLI")
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --precision=2                          \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --mode=throughput                      \
        --time-unit=seconds                    \
        --mode=throughput                      \
        --precision=0                          \
        -vvv
	$(call title,"Build reports - HTML")
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --output=jbzoo-html-time               \
        --mode=time                            \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo                       \
        --report=jbzoo-table                   \
        --output=jbzoo-html-throughput         \
        --time-unit=seconds                    \
        --mode=throughput                      \
        --precision=0                          \
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
	$(call title,"Build reports - Markdown")
	@php `pwd`/vendor/bin/phpbench run         \
        --tag=jbzoo_readme                     \
        --group=readme                         \
        --store                                \
        --revs=100000                          \
        --iterations=10                        \
        --warmup=2                             \
        --stop-on-error                        \
        -vvv
	@php `pwd`/vendor/bin/phpbench report      \
        --uuid=tag:jbzoo_readme                \
        --report=jbzoo-markdown                \
        --output=jbzoo-md                      \
        --precision=2                          \
        -vvv
	@cat `pwd`/build/phpbench.md
