var phpangularmodules = phpangularmodules || [];
phpangularmodules.push('ngRoute');
var phpangularModule = angular.module('phpangularModule', phpangularmodules);

module PhpangularModule {

    export var servername = '';
    export var version = '0000002';
    export var debug = true;
    phpangularModule.config(['$routeProvider', '$locationProvider',
    // Define all basics route for phpangular
      function($routeProvider, $locationProvider) {
        $locationProvider.html5Mode(true);
        $routeProvider
        .when('/:module?-:method?-:id?', {
                templateUrl: function(params) {
                    return servername + "template/" + params.module + "/"+ params.method + "/" + params.method + '-' + version + '.html';
                }
            })
        .when('/:module?-:method?', {
                templateUrl: function(params) {
                    if (typeof(params.module) === 'undefined') {
                        params.module = "home";
                    }
                    return servername + "template/" + params.module + "/"+ params.method + "/" + params.method + '-' + version + '.html';
                }
        })
            .when('/:module?', {
                    templateUrl: function(params) {
                        if (typeof(params.module) === 'undefined') {
                            params.module = "home";
                        }
                        return servername + "template/" + params.module + "/" +params.module + "/" + params.module + '-' + version + '.html';
                    }
            })
            .otherwise({
                templateUrl: servername + 'template/home/home/home' + '-' + version + '.html'
            });
      }]);
}
