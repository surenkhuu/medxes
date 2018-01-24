'use strict';

/**
 * Config for the router
 */

angular.module('app')

  .config(
    [          '$stateProvider', '$urlRouterProvider',
      function ($stateProvider,   $urlRouterProvider) {

          $urlRouterProvider
              .otherwise('/access/signin');

          $stateProvider
              .state('app', {
                  abstract: true,
                  url: '/app',
                  templateUrl: 'tpl/app.html'
              })
              .state('app.form', {
                url: '/form',
                template: '<div ui-view class="fade-in"></div>',
            }) 
            .state('app.form.wizard', {
                url: '/wizard',
                templateUrl: 'tpl/form_wizard.html'
            })
            .state('app.form.fileupload', {
                url: '/fileupload',
                templateUrl: 'tpl/form_fileupload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load('angularFileUpload').then(
                            function(){
                               return $ocLazyLoad.load('js/controllers/file-upload.js');
                            }
                        );
                    }]
                }
            })
            .state('app.page', {
                url: '/page',
                template: '<div ui-view class="fade-in-down"></div>'
            })
            .state('app.page.invoice', {
                url: '/invoice',
                templateUrl: 'tpl/page_invoice.html'
                ,
                 resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                        return $ocLazyLoad.load('barcodeGenerator').then(
                            function(){
                               return $ocLazyLoad.load('js/controllers/invoice.js');
                            }
                        );
                    }]
                }
            })
            .state('app.table', {
                url: '/table',
                template: '<div ui-view></div>'
            })
            .state('app.table.static', {
                url: '/static',
                templateUrl: 'tpl/table_static.html'
            })
            .state('app.table.datatable', {
                url: '/datatable',
                templateUrl: 'tpl/table_datatable.html'
            })
              .state('app.blank', {
                  url: ''
              })
              .state('app.dashboard', {
                  url: '/dashboard',
                  templateUrl: 'tpl/app_dashboard.html',
                  resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                        return $ocLazyLoad.load(['js/controllers/dashboard.js']);
                    }]
                  }
              })
              .state('app.form.validation', {
                url: '/validation',
                templateUrl: 'tpl/form_validation.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                        return $ocLazyLoad.load('dynform').then(
                            function(){
                               return $ocLazyLoad.load('js/controllers/form.js');
                            }
                        );
                    }]
                }
            })
            .state('app.form.sell', {
                url: '/validation',
                templateUrl: 'tpl/form_sell.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                        return $ocLazyLoad.load('dynform').then(
                            function(){
                               return $ocLazyLoad.load('js/controllers/form_sell.js');
                            }
                        );
                    }]
                }
            })
               .state('app.form.passChange', {
                   url:'/passChange',
                  params : {id: null},
                  templateUrl: 'tpl/form_passChange.html'
                 ,
                  resolve: {
                    deps: ['uiLoad',
                        function( uiLoad){
                          return uiLoad.load('js/controllers/form_passChange.js');
                      }]
                  }
              })
              
              .state('access', {
                  url: '/access',
                  template: '<div ui-view class="fade-in-right-big smooth"></div>'
              })
              .state('access.signin', {
                  url: '/signin',
                  templateUrl: 'tpl/page_signin.html',
                  resolve: {
                      deps: ['uiLoad',
                        function( uiLoad ){
                          return uiLoad.load( ['js/controllers/signin.js'] );
                      }]
                  }
              })
              .state('access.signup', {
                  url: '/signup',
                  templateUrl: 'tpl/page_signup.html',
                  resolve: {
                      deps: ['uiLoad',
                        function( uiLoad ){
                          return uiLoad.load( ['js/controllers/signup.js'] );
                      }]
                  }
              })
              .state('access.forgotpwd', {
                  url: '/forgotpwd',
                  templateUrl: 'tpl/page_forgotpwd.html'
              })
              .state('access.404', {
                  url: '/404',
                  templateUrl: 'tpl/page_404.html'
              })

              .state('layout', {
                  abstract: true,
                  url: '/layout',
                  templateUrl: 'tpl/layout.html'
              })
              .state('layout.app', {
                  url: '/app',
                  views: {
                      '': {
                          templateUrl: 'tpl/layout_app.html'
                      },
                      'footer': {
                          templateUrl: 'tpl/layout_footer_fullwidth.html'
                      }
                  },
                  resolve: {
                      deps: ['uiLoad',
                        function( uiLoad ){
                          return uiLoad.load( ['js/controllers/tab.js'] );
                      }]
                  }
              })
      }
    ]
  )
.run(
    [ '$rootScope', '$state', '$stateParams', 'Data',
      function ($rootScope,  $state,  $stateParams, Data) {
          $rootScope.$state = $state;
          $rootScope.$stateParams = $stateParams;

          $rootScope.$on("$stateChangeStart", function(e, toState, toParams, fromState, fromParams) {
            $rootScope.authenticated = false;
            Data.get('session').then(function (results) {
                if (results.userid) {
                    console.log(results)
                    $rootScope.authenticated = true;
                    $rootScope.userid = results.userid;
                    $rootScope.fullname = results.fullname;
                    $rootScope.id = results.id;
                    $rootScope.userinfo = results.userinfo;
                } else {
                    var nextUrl = toState.module;
                    if (nextUrl == 'signup' || nextUrl == 'login') {

                    } else {
                        $state.go("access.signin");
                    }
                }
            });
        });

      }
    ]
  );
