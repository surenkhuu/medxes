'use strict';

/* Controllers */
  // signin controller
app.controller('SigninFormController', ['$scope', '$http', '$state', 'Data', function($scope, $http, $state, Data) {
    $scope.user = {};
    $scope.authError = null;
    $scope.login = function() {
      $scope.authError = null;
    var customer = {userid: $scope.user.userid, password: $scope.user.password};
       Data.post('login', {
            customer: customer
        }).then(function (results) {
            Data.toast(results);
            if (results.status == "success") {
                     $state.go('app.dashboard');
            }
        });
    };
  }])
;
