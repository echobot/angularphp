  /****************************************************************************
   * AngularPHP Endpoint
   ****************************************************************************
   * You can access this endpoint's clases by including the following module
   * in your Angular app:
   * 
   *   $MODULE$
   *
   * like so:
   * 
   *   angular.module('my.module', [$MODULE$]);
   *
   * @copyright 2014 Echobot Media Technologies GmbH
   * @author    Dave Kingdon <kingdon@echobot.de>
   * 
   ***************************************************************************/

  /* jshint sub:true */

  /**
   * A self-executing anonymous function to keep the scope clean.  All functions
   * and variables defined within will not leak into the global scope.
   */
  (function(angular) {

    var moduleUrl = '$URI$',
      moduleName = $MODULE$,
      app = angular.module(moduleName, []),
      manifest = new Manifest($MANIFEST$),
      debug = $DEBUG$,
      modelDefinitions = {},
      entityStorage = {},
      objectIdIndex = 0,
      classKey = '__class__',
      idKey = '__id__',
      definitionKey = '__definition__',
      strStatic = 'static',
      strClass = 'class',
      strReturn = 'return',
      strExtends = 'extends';

    /**
     * A description of the classes known to AngularPHP
     * @class
     * @param {object} manifest A plain object decribing the contents of the
     * manifest
     */
    function Manifest(manifest) {

      var classes = {};

      angular.forEach(manifest, function(def) {
        classes[def.name] = new ClassEntry(def);
      });

      /**
       * A description of a single class
       * @param {object} classDef A plain object describing a class
       */
      function ClassEntry(classDef) {
        angular.extend(this, classDef);
      }

      /**
       * Returns the ClassEntry for the specified class
       * @param  {string|object} className The class name, or an object of the
       *                                   class
       * @throws If the specified class is not present in the manifest
       * @return {ClassEntry} The class entry
       */
      this.getClassDefinition = function(className) {
        if ((angular.isObject(className) || angular.isFunction(className)) &&
          angular.isDefined(className[classKey])) {
          className = className[classKey];
        }

        if (!this.containsClass(className)) throw 'Class does not exist';
        return classes[className];
      };

      this.getClassDefinitions = function() {
        return classes;
      };

      this.containsClass = function(className) {
        if (angular.isObject(className)) {
          if (className.hasOwnProperty(classKey)) className = className[classKey];
        }
        return classes.hasOwnProperty(className);
      };

    }

    function isEntity(obj) {
      return obj.hasOwnProperty(classKey) && manifest.containsClass(obj[classKey]);
    }

    /**
     * Generates a string id for a given object, based on its identifiers as
     * defined in the manifest
     *
     * @param  {Object} obj - the object for which to generate the id
     * @return {string} - the id
     */
    function generateIdForObject(obj) {
      var className, identifiers = {}, def, id = null;

      if (isEntity(obj)) {
        className = obj[classKey];
      } else {
        className = obj[definitionKey][strClass];
      }

      if (obj.hasOwnProperty(idKey)) return obj[idKey];

      def = manifest.getClassDefinition(obj);

      if (def.identifiers.length) {
        var identifiersUsed = 0;

        angular.forEach(def.identifiers, function(prop) {
          if (obj[prop] === null) return;
          identifiers[prop] = obj[prop];
          identifiersUsed++;
        });

        if (identifiersUsed) {
          id = obj[classKey] + ':' + hashObject(identifiers);
        }
      }

      if (null === id) {
        id = obj[classKey] + ':' + objectIdIndex++;
      }

      return id;
    }


    /**
     * A method which turns an object into a string hash
     *
     * @param  {Object} obj
     * @return {string} the hash
     */
    function hashObject(obj) {
      var hash = [];

      angular.forEach(obj, function(v, k) {
        hash.push(k + '=' + String(v));
      });

      return hash.join(',');
    }


    /**
     * The base model, used as a prototype for each model, except those which
     * extend another model. This class will *always* be in the inheritance
     * chain for a model created by this module
     *
     * @constructor
     */
    function BaseModel() {}
    

    /**
     * A utility function to make a function with a specific name
     * 
     * @param  {string} name - the name of the model to create
     * @return {Object}
     */
    function makeModelClass(name) {
      /* jshint evil:true */
      var modelClass = Function(
        'return function ' + name + '(){}'
      )();

      return modelClass;
    }


    /**
     * A utility function to conver the arguments object into an array
     * suitable for conversion to JSON
     * 
     * @param  {Arguments} args
     * @return {Array.<mixed>}
     */
    function argumentsToArray(args) {
      return Array.prototype.slice.call(args, 0);
    }


    /**
     * A utility function to output data to the console
     * @param  {object} toLog The object to output
     */
    function logTable(toLog, type) {
      type = type || 'debug';

      function indentString(str, n) {
        var pattern = new RegExp('[\n]', 'g'),
          replace = '\n' + new Array(parseInt(n) + 1).join(' ');
        return (str || '').replace(pattern, replace);
      }

      var kl = 0;
      angular.forEach(toLog, function(v, k) {
        kl = Math.max(kl, k.length);
      });

      angular.injector(['ng']).invoke(['$log', function($log) {
        angular.forEach(toLog, function(v, k) {
          if (angular.isString(v)) v = indentString(v, kl + 2);
          k = new Array((kl - k.length) + 1).join(' ') + k;
          $log[type](k + ':', v);
        });
      }]);
    }


    /**
     * Serialises something into an array.  If an entity is encountered,
     * that is serialised with its __class__ and __id__ properties, allowing
     * it to be identified on both the server side and the client side.
     * @param  {mixed}        something  Something to serialise
     * @param  {object|null}  serialised A hash containing the IDs of 
     *                                   serialised entities
     * @return {mixed}        the serialised result
     */
    function serialise(something, serialised) {
      var a, o, id;
      serialised = serialised || {};

      if (angular.isArray(something)) {
        a = [];
        angular.forEach(something, function(v, i) {
          a.push(serialise(v, serialised));
        });
        return a;
      } else if ((angular.isFunction(something) && something[classKey]) || angular.isObject(something)) {
        o = {};

        if (angular.isDefined(something[classKey])) {
          
          o[classKey] = something[classKey];

          if (!angular.isFunction(something) && !angular.isDefined(something[idKey])) {
            id = generateIdForObject(something);
            something[idKey] = id;
            entityStorage[id] = something;
          }

          if (something.hasOwnProperty(idKey)) {
            o[idKey] = something[idKey];
          }
        }

        if (angular.isDefined(something[idKey])) {
          id = something[idKey];
        }

        if (!id || !serialised.hasOwnProperty(o[idKey])) {

          if (id) serialised[id] = true;

          angular.forEach(something, function(v, k) {
            if (something.hasOwnProperty(classKey) &&
              k == definitionKey) {
                return;
            }

            if (angular.isDefined(something[definitionKey])) {
              if (angular.isObject(something[definitionKey].properties)) {
                if (something[definitionKey].properties.hasOwnProperty(k)) {
                  if (something[definitionKey].properties[k].readOnly) return;
                }
              }
            }

            o[k] = serialise(v, serialised);
          });
        }

        return o;
      } else if (angular.isFunction(something)) {
        return;
      } else {
        return something;
      }
    }

    if (debug) {
      angular.injector(['ng']).invoke(['$log', function($log) {
        $log.info('AngularPHP', moduleName, 'manifest:', manifest.getClassDefinitions());
      }]);
    }

    /**
     * Iterate over the manifest and create a service for each entry
     */
    angular.forEach(manifest.getClassDefinitions(), function(def) {

      app.service(def.name, ['$injector', '$q', '$http', '$rootScope', '$log', function($injector, $q, $http, $rootScope, $log) {

        if (!angular.isFunction(BaseModel.prototype.$clone)) {
          /**
           * a utility method for cloning an entity
           * @return {Object}
           */
          BaseModel.prototype.$clone = function() {
            return clone(this);
          };
        }


        /**
         * Returns an entity based on the data passed
         *
         * @param  {object} data - the data describing the object and its
         * contents
         * @return {object} - the created entity
         */
        function createEntity(data, ignoreEntityStorage) {
          if (!manifest.containsClass(data)) return data;

          var id = generateIdForObject(data), entity;

          if (data.hasOwnProperty('__old_id__')) {
            var oldId = data['__old_id__'];
            delete data['__old_id__'];
            if (entityStorage.hasOwnProperty(oldId)) {
              entityStorage[id] = entityStorage[oldId];
            }
          }

          if (!entityStorage.hasOwnProperty(id)) {
            if (!$injector.has(data[classKey])) {
              throw 'Model ' + data[classKey] + ' does not exist';
            }
            entity = new ($injector.get(data[classKey]))();
          } else {
            entity = entityStorage[id];
          }

          angular.extend(entity, data);

          if (!ignoreEntityStorage) entityStorage[id] = entity;

          return entity;
        }


        /**
         * Parses the passed data, turning references to entities into those
         * entities
         *
         * @param  {mixed} data
         * @return {mixed}
         */
        function unserialise(data, ignoreEntityStorage) {
          ignoreEntityStorage = ignoreEntityStorage || false;

          if (angular.isArray(data) || angular.isObject(data)) {
            angular.forEach(data, function(e, i) {
              data[i] = unserialise(e, ignoreEntityStorage);
            });

            if (data.hasOwnProperty(classKey)) {
              data = createEntity(data, ignoreEntityStorage);
            }
          }

          return data;
        }


        /**
         * Clones an entity (or anything else)
         * 
         * @param  {mixed} something the thing to clone
         * @return {mixed}           the cloned copy
         */
        function clone(something) {
          var a, o;
          if (angular.isArray(something)) {
            a = [];
            angular.forEach(something, function(v) {
              a.push(clone(v));
            });
            return a;
          } else if (angular.isObject(something)) {
            if (something instanceof BaseModel) {
              return unserialise(something, true);
            } else {
              o = {};
              angular.forEach(something, function(v, k) {
                o[k] = clone(v);
              });
            }
          } else {
            return something;
          }
        }


        /**
         * Used to call a method on an object
         *
         * @param  {string} modelClass - the class of the object in question
         * @param  {string} method - the method to be called
         * @param  {Array}  args - the arguments to be passed to the method
         * @param  {Object} context - the contents of the JavaScript object
         * upon which the method was called
         * @return {mixed} - the result of the method
         */
        function callMethod(modelClass, method, args, context, config) {

          var data = {
            action: 'method',
            payload: {
              'class': modelClass,
              name: method,
              arguments: args,
              context: context
            }
          };

          config = config || {};

          /**
           * 'text/plain' is used to avoid pre-flighting on CORS requests
           */
          angular.extend(config, {
            headers: {
              'Content-Type': 'text/plain'
            },
            withCredentials: true
          });

          return $http.post(moduleUrl, data, config || {})
            .error(function(response) {
              $rootScope.$broadcast('EntityError', response);
            });
        }


        /**
         * Creates a function which will call a method on an object/class via
         * HTTP
         *
         * @param  {string} method - the method name
         * @return {Object} - a promise to be resolved/rejected upon
         * completion of the call
         */
        function createMethod(methodDef) {
          var configOverride = {}, method = function() {
            var deferredMethod = $q.defer(),
              args = argumentsToArray(arguments),
              self = this,
              context,
              callStart;

            if (args.length < methodDef.parameterCount) {
              throw 'Method ' + methodDef.name + '() requires ' +
                methodDef.parameterCount + ' parameters, but ' + args.length +
                ' given.';
            }

            if (methodDef[strStatic]) {
              context = serialise($injector.get(def.name));
            } else {
              context = serialise(this);
            }

            if (debug) {
              callStart = new Date().getTime();
            }

            callMethod(def.name, methodDef.name, serialise(args), context,
              configOverride)
              .success(function(response) {

                var returnedState;

                if (debug) {
                  returnedState = angular.copy(response.state || null);
                }

                /**
                 * This ensures all entities are updated with any values
                 * returned from the server, either in the return value of the 
                 * call, or in the context itself.
                 */
                unserialise(response);

                if (debug) {
                  var duration = (new Date().getTime()) - callStart;

                  $log.debug('Call to',
                    methodDef[strStatic] ? strStatic : 'dynamic', 'method',
                    methodDef.name + '()'
                  );

                  logTable({
                    'Class': def.name,
                    'Context': context,
                    'Returned value': response[strReturn],
                    'Output': response.output || 'none',
                    'Returned state': returnedState || 'none',
                    'Duration': duration + 'ms'
                  });

                }

                deferredMethod.resolve(response[strReturn]);
              })
              .error(function(response) {
                if (response && debug) {
                  
                  $log.error('Failed call to',
                    methodDef[strStatic] ? strStatic : 'dynamic', 'method',
                    methodDef.name + '()'
                  );

                  logTable({
                    'Context': self,
                    'Class': response[strClass],
                    'Code': response['code'],
                    'Message': response.message || 'none',
                    'Output': response.output || 'none',
                    'Trace': response.trace || 'none'
                  }, 'warn');
                }

                deferredMethod.reject(response);
              });

            return deferredMethod.promise;
          };

          /**
           * A method allowing the $http configuration to be altered for each
           * call to a method
           * 
           * @param  {object}   config The configuration to be applied
           * @return {function}        The method being configured
           */
          method.httpConfig = function(config) {
            if (angular.isObject(config)) {
              configOverride = config;
            }
            return method;
          };

          return method;
        }


        /**
         * Create the model's constructor
         */
        var modelClass = makeModelClass(def.name);


        /**
         * Determine which class to inherit from.  If no inheritance defined,
         * BaseModel is used.
         */
        if (def[strExtends] && modelDefinitions.hasOwnProperty(def[strExtends])) {
          modelClass.prototype = new ($injector.get(def[strExtends]))();
        } else {
          modelClass.prototype = new BaseModel();
        }

        modelDefinitions[def.name] = modelClass;

        modelClass[classKey] = def.name;
        modelClass[definitionKey] = {};

        modelClass.prototype[classKey] = def.name;
        modelClass.prototype[definitionKey] = modelClass[definitionKey];

        angular.extend(modelClass[definitionKey], def);


        /**
         * Add the properties defined in the manifest to the nascent model
         * class
         */
        angular.forEach(def.properties, function(property) {
          if (property[strStatic]) {
            modelClass[property.name] = property.value;
          } else {
            modelClass.prototype[property.name] = property.value;
          }
        });


        /**
         * Add the methods defined in the manifest to the nascent model class
         */
        angular.forEach(def.methods, function(method) {
          if (method[strStatic]) {
            modelClass[method.name] = createMethod(method);
          } else {
            modelClass.prototype[method.name] = createMethod(method);
          }
        });


        /**
         * Add the constants defined in the manifest to the nascent model class
         */
        angular.forEach(def.constants, function(constant, name) {
          modelClass[name] = constant.value;
        });


        /**
         * Finally, return the newly-created model class.  This is what will
         * be injected in any angular module which uses this module.
         */
        return modelClass;
      }]);

      
      /**
       * Ensures the models are defined before they are injected.  This is
       * *essential* for inheritance, as AngularJS lazily-loads services when
       * they are first called (for performance reasons) and this forces
       * AngularJS to load all these model definitions at once.
       * 
       * @todo Find a better way to achieve this.
       */
      app.run([def.name, '$log', function(model, $log) {
        if (debug) $log.info('[' + moduleName + ']', 'Model defined:', def.name);
      }]);

    });

  })(angular);