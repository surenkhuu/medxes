'use strict';

/* Controllers */
angular.module('app')
  .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window', '$http' , 'Data', 'toaster', '$state',
    function( $scope,   $translate,   $localStorage,   $window, $http , Data, toaster, $state) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');

      // config
      $scope.app = {
        name: 'Medxes',
        version: '1.1',
        // for chart colors
        color: {
          primary: '#7266ba',
          info:    '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger:  '#f05050',
          light:   '#e8eff0',
          dark:    '#3a3f51',
          black:   '#1c2b36'
        },
        settings: {
          themeID: 1,
          navbarHeaderColor: 'bg-dark header',//'bg-black',
          navbarCollapseColor: 'bg-white-only header',//'bg-white-only',
          asideColor: 'bg-white-only',//'bg-black',bg-light dker'
          headerFixed: true,
          asideFixed: false,
          asideFolded: true,
          asideDock: true,
          container: false
        }
      }

      // save settings to local storage
      if ( angular.isDefined($localStorage.settings) ) {
        $scope.app.settings = $localStorage.settings;
      } else {
        $localStorage.settings = $scope.app.settings;
      }
      $scope.$watch('app.settings', function(){
        if( $scope.app.settings.asideDock  &&  $scope.app.settings.asideFixed ){
          // aside dock and fixed must set the header fixed.
          $scope.app.settings.headerFixed = true;
        }
        // save to local storage
        $localStorage.settings = $scope.app.settings;
      }, true);

      function isSmartDevice( $window )
      {
          // Adapted from http://www.detectmobilebrowsers.com
          var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
          // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
          return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
      }

      $scope.logout = function () {
        Data.get('logout').then(function (results) {
            Data.toast(results);
            $state.go('access.signin');
        });
       }
    $scope.IsJsonString  = function (str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
    }

    $scope.tagTransform = function (value,name) {
        var item = {
            name: name,
            value: value
        };
        return item;
      };
  $scope.getCombo  = function(tablename,id,view,related,where){
    var items = [];
    Data.post('getCombo', {
      params: { table: tablename, related: related, where: where, id: id, name: view }
        }).then(function (results) {
            if (results.status == "success") {
                if(results.data){
            var obj = $.parseJSON(results.data);
            $.each(obj, function(index, el) {
                items.push($scope.tagTransform(el[id], el[view]));
                                   }
               );
             }
            }
        });
        return items;
   }

  }]);


angular.module('app')
  .controller('mainMenuCtrl', ['$scope', '$translate', '$localStorage', '$window', '$http' , 'Data', 'toaster', '$state',
    function( $scope,   $translate,   $localStorage,   $window, $http , Data, toaster, $state) {
 $scope.getMenu = function () {
    var menus = [];
     var submenus = [];
     var item = '';
    Data.get('getMenu', {
        }).then(function (results) {
            if (results.status == "success") {
                if(results.data){

            var obj = $.parseJSON(results.data);
            $.each(obj, function(index, el) {
            if(el.mname != item)
            {
             menus.push({
             name: el.mname,
             iconCls: el.miconCls !='' ? el.miconCls : 'fa fa-folder',
             sub : [{
              name: el.sname,
              iconCls: el.siconCls !='' ? el.siconCls : 'fa fa-circle-o',
              url: el.surl !='' ? el.surl : 'app.dashboard'
             }]
            });
            }
            else
            {
           $.grep(menus, function(e){
            if(e.name == el.mname)
               e.sub.push({
                name: el.sname,
              iconCls: el.siconCls,
              url: el.surl !='' ? el.surl : 'app.dashboard'
              });
               });
            }
             item = el.mname;
                                   }
               );
             }
            }
        });
        return menus;
    }
  //  $scope.menus = $scope.getMenu();
      }]);
