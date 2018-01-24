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

              // fullCalendar
              .state('app.calendar', {
                  url: '/calendar',
                  templateUrl: 'tpl/app_calendar.html',
                  // use resolve to load other dependences
                  resolve: {
                      deps: ['$ocLazyLoad', 'uiLoad',
                        function( $ocLazyLoad, uiLoad ){
                          return uiLoad.load(
                            ['vendor/jquery/fullcalendar/fullcalendar.css',
                              'vendor/jquery/fullcalendar/theme.css',
                              'vendor/jquery/jquery-ui-1.10.3.custom.min.js',
                              'vendor/libs/moment.min.js',
                              'vendor/jquery/fullcalendar/fullcalendar.min.js',
                              'js/app/calendar/calendar.js']
                          ).then(
                            function(){
                              return $ocLazyLoad.load('ui.calendar');
                            }
                          )
                      }]
                  }
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
