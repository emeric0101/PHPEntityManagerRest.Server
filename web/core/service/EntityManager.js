"use strict";
var RepositoryService_1 = require("./RepositoryService");
var AEntity_1 = require("../Entity/AEntity");
var EntityManager = (function () {
    function EntityManager($ajax, $url, $repo, $injector) {
        this.$ajax = $ajax;
        this.$url = $url;
        this.$repo = $repo;
        this.$injector = $injector;
        this.persistObjs = [];
    }
    EntityManager.prototype.getRespository = function (name) {
        if (name == '') {
            console.error("getRepository from EntityManager : bad name : ", name);
            return null;
        }
        if (Emeric0101.PHPAngular.Repository[name + "Repository"] === 'function') {
            return this.$injector.get(name + "Repository");
        }
        return this.$repo;
    };
    EntityManager.prototype.getPersistObjs = function () {
        return this.persistObjs;
    };
    EntityManager.prototype.persist = function (obj, exclude) {
        if (exclude === void 0) { exclude = []; }
        var $this = this;
        for (var i in this.persistObjs) {
            if (this.persistObjs[i] == obj) {
                return;
            }
        }
        exclude.push(obj);
        var checkForEntity = function (v) {
            for (var i in exclude) {
                if (exclude[i] == v) {
                    return false;
                }
            }
            if (v instanceof AEntity_1.Model) {
                $this.persist(v, exclude);
                return true;
            }
            return false;
        };
        for (var j in obj) {
            if (checkForEntity(obj[j])) {
                continue;
            }
        }
        this.persistObjs.push(obj);
    };
    EntityManager.prototype.clear = function () {
        this.persistObjs = [];
    };
    EntityManager.prototype.save = function (obj, callback) {
        var $this = this;
        var objs = {};
        var dataToSend = {};
        if (!obj.getChanged()) {
            callback(true, []);
            return;
        }
        for (var i in obj) {
            var value = obj[i];
            if (i == 'foreignKeyRequests') {
                continue;
            }
            if (value instanceof RepositoryService_1.RepositoryService) {
                continue;
            }
            if (typeof (value) === 'function') {
                continue;
            }
            if (value === null || typeof (value) === 'undefined') {
                continue;
            }
            if ((typeof (value) === 'array' || typeof (value) === 'object') && value.length > 0 && typeof (value[0].getId) === 'function') {
                var nvalue = [];
                for (var objIndex in value) {
                    nvalue[objIndex] = value[objIndex].getId();
                }
                objs[i] = nvalue;
                continue;
            }
            if (typeof (value.getId) === 'function') {
                value = value.getId();
            }
            if (typeof (value) === 'object' && typeof (value.entity) === 'string') {
                value = value.id;
            }
            objs[i] = value;
        }
        dataToSend[obj.getName()] = objs;
        this.$ajax.post(this.$url.makeApi(obj.getName(), 'post', obj.getId()), dataToSend, function (r) {
            var data = r.data;
            var errorMsg = 'OK';
            if (data['errMsg'] !== undefined) {
                errorMsg = data['errMsg'];
            }
            if (data.success !== true) {
                callback(false, errorMsg);
                return;
            }
            if (typeof (data[obj.getName()]) !== 'undefined') {
                var nobj = $this.$repo.EntityFromJson(data[obj.getName()], obj.getName());
                for (var i in nobj) {
                    obj[i] = nobj[i];
                }
            }
            callback(true, []);
        }, function () {
            callback(false, 'UNABLE_TO_CONNECT');
        });
    };
    EntityManager.prototype.flush = function (callback, autoclear) {
        var _this = this;
        if (autoclear === void 0) { autoclear = true; }
        if (typeof (callback) === "undefined") {
            callback = function (r) { };
        }
        if (this.persistObjs.length === 0) {
            return;
        }
        var persistObjs = this.persistObjs;
        if (autoclear) {
            this.clear();
        }
        var i = 0;
        var magicFunction = function (response, errorMsg) {
            if (!response) {
                callback(false, errorMsg);
                return;
            }
            i++;
            if (i >= persistObjs.length) {
                _this.$repo.clearCache();
                callback(true, errorMsg);
                return;
            }
            _this.save(persistObjs[i], magicFunction);
        };
        this.save(persistObjs[0], magicFunction);
    };
    return EntityManager;
}());
exports.EntityManager = EntityManager;
EntityManager.$inject = ['AjaxService', 'UrlService', 'RepositoryService', '$injector'];
