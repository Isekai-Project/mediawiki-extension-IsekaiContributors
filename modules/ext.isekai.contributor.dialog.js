if (!('isekai' in window)) window.isekai = {};

/**
 * @class isekai.ContribDialog
 * @param {*} config 
 */
isekai.ContribDialog = function IsekaiContribDialog(config) {
    isekai.ContribDialog.super.call(this, config);
    this.broken = false;
};

OO.inheritClass(isekai.ContribDialog, OO.ui.ProcessDialog);
isekai.ContribDialog.static.name = 'isekaicontrib-dialog';
isekai.ContribDialog.static.title = mw.msg('isekaicontrib-dialog-title');

isekai.ContribDialog.static.actions = [
    { action: 'cancel', label: mw.msg('isekaicontrib-dialog-cancel'), flags: ['safe', 'close'] }
];

isekai.ContribDialog.prototype.initialize = function () {
    isekai.ContribDialog.super.prototype.initialize.apply(this, arguments);
    this.api = new mw.Api();

    this.content = new OO.ui.PanelLayout({ padded: false });

    //加载动画
    this.loadingWidget = new OO.ui.ProgressBarWidget({
        progress: false,
        classes: ['isekai-contrib-loading']
    });
    this.content.$element.append(this.loadingWidget.$element);
    //贡献者列表容器
    this.content.$element.append('<div class="isekai-contrib-list"></div>');
    this.contribContainer = this.content.$element.find('.isekai-contrib-list');

    this.$body.append(this.content.$element);
}

isekai.ContribDialog.prototype.getUserAvatar = function (userName) {
    var template = mw.config.get('wgIsekaiContributorAvatar');
    if (template) {
        return template.replace(/\%s/g, userName);
    } else {
        return '';
    }
};

/**
 * 生成用户栏目html
 * @param {any} userData - 用户信息
 * @returns {string} 生成的html
 */
isekai.ContribDialog.prototype.getUserListItem = function (userData) {
    return  '<a class="isekai-list-item" href="' + mw.util.getUrl(userData.user_page) + '" target="_blank">' +
                '<div class="isekai-list-item-avatar"><img src="' + this.getUserAvatar(userData.user_name) + '"></div>' +
                '<div class="isekai-list-item-content">' +
                    '<div class="isekai-list-item-title">' + userData.display_name + '</div>' +
                    '<div class="isekai-list-item-text">@' + userData.user_name + '</div>' +
                '</div>' +
            '</a>';
};

/**
 * 加载贡献者列表
 */
isekai.ContribDialog.prototype.loadContributors = function () {
    var currentPageId = mw.config.get('wgArticleId');
    this.api.get({
        action: 'query',
        prop: 'pagecredit',
        pageids: currentPageId,
    }).done((data) => {
        if('query' in data && 'pages' in data.query){
            if(currentPageId in data.query.pages){
                var pageData = data.query.pages[currentPageId];
                if('missing' in pageData) return;
                if(!pageData.pagecredit || 'error' in pageData.pagecredit) return;
                
                var creditData = pageData.pagecredit;
                this.loadingWidget.toggle(false);
                this.contribContainer.empty();
                if('creator' in creditData){
                    this.contribContainer.append('<div class="isekai-list-item-sub">' + mw.msg('isekaicontrib-dialog-subtitle-creator') + '</div>');
                    this.contribContainer.append(this.getUserListItem(creditData.creator));
                }
                if('last_editor' in creditData){
                    this.contribContainer.append('<div class="isekai-list-item-sub">' + mw.msg('isekaicontrib-dialog-subtitle-last-editor') + '</div>');
                    this.contribContainer.append(this.getUserListItem(creditData.last_editor));
                }
                if(('contributors' in creditData) && creditData.contributors.length > 0){
                    this.contribContainer.append('<div class="isekai-list-item-sub">' + mw.msg('isekaicontrib-dialog-subtitle-contributors') + '</div>');
                    creditData.contributors.forEach((editor) => {
                        this.contribContainer.append(this.getUserListItem(editor));
                        this.contribContainer.append('<hr class="isekai-list-item-divider">');
                    });
                }
            }
        }
    });
};

isekai.ContribDialog.prototype.getBodyHeight = function () {
    return 400;
};

isekai.ContribDialog.prototype.getSetupProcess = function (data) {
    var _this = this;
    return isekai.ContribDialog.super.prototype.getSetupProcess.call(this, data).next(function(){
        _this.loadContributors();
        return true;
    });
}

isekai.ContribDialog.prototype.getActionProcess = function (action) {
    var dialog = this;
    return new OO.ui.Process(function () {
        dialog.close({ action: action });
    });
};

// ==============================================================
var contribDialog = new isekai.ContribDialog({});
var windowManager = new OO.ui.WindowManager();
$(document.body).append(windowManager.$element);
windowManager.addWindows([contribDialog]);

$('.isekai-contrib-open-dialog').click(function () {
    windowManager.openWindow(contribDialog);
    return false;
});