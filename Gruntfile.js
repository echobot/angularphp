module.exports = function(grunt) {

  grunt.initConfig({
    exec: {
      composer: {
        cmd: 'composer install'
      }
    },

    uglify: {
      all: {
        files: {
          'src/js/echobot-angularphp.min.js': ['src/js/echobot-angularphp.js']
        }
      }
    },

    jshint: {
      all: 'src/**/echobot-angularphp.js'
    },

    phplint: {
      all: ['src/**/*.php', 'test/**/*.php']
    },

    phpunit: {
      classes: {
        dir: 'tests/src/php/'
      },
      options: {
        bin: 'vendor/bin/phpunit',
        bootstrap: 'vendor/autoload.php',
        colors: true
      }
    },

    watch: {
      test: {
        files: ['src/php/**/*', 'tests/src/php/**/*', 'tests/src/resources/**/*'],
        tasks: ['phpunit']
      }
    }

  });

  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-phplint');
  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('audit:js', ['jshint']);
  grunt.registerTask('audit:php', ['exec:composer', 'phplint', 'phpunit']);
  grunt.registerTask('audit', ['audit:js', 'audit:php']);

  grunt.registerTask('build', ['jshint', 'uglify']);
};