    var _hmt = _hmt || [];
    (function() {
      var hm = document.createElement("script");
      hm.src = "https://hm.baidu.com/hm.js?571f1aa56a727e619f90ecb31ae92898";
      var s = document.getElementsByTagName("script")[0];
      s.parentNode.insertBefore(hm, s);
    })();
    var share_info = {
        title: "爱阅赚",
        desc: "分享文章，赚零花钱！",
        link: window.location.href,
        imgUrl: "https://new-weizhuan.oss-cn-shenzhen.aliyuncs.com/common/logo_244%2A244.png",
        success: function () {
        }
    }
    $.get("/index.php/App/Js/api?url=" + encodeURIComponent(location.href.split('#')[0]), "", function(req){
        if (req.code == 0) {
            wx.config(req.data);
            wx.ready(function() {
                wx.onMenuShareTimeline(share_info);
                wx.onMenuShareAppMessage(share_info);
                wx.onMenuShareQQ(share_info);
                wx.onMenuShareWeibo(share_info);
                wx.onMenuShareQZone(share_info);
            });
        }
    });