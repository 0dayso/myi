/**
* 树表的jQuery插件
* @author benzhan (詹潮江)
* @date 2011-05-04
*/

(function ($) {
    var path = '/jsplug/treeTable';
    var defaults = {
        openImg: path + "/images/tv-collapsable.gif",
        shutImg: path + "/images/tv-expandable.gif",
        leafImg: path + "/images/tv-item.gif",
        lastOpenImg: path + "/images/tv-collapsable-last.gif",
        lastShutImg: path + "/images/tv-expandable-last.gif",
        lastLeafImg: path + "/images/tv-item-last.gif",
        vertLineImg: path + "/images/vertline.gif",
        blankImg: path + "/images/blank.gif",
        collapse: false,
        column: 0,
        striped: false,
        highlight: false,
        onselect: false
    };

    $.fn.treeTable = function (opts) {
        opts = $.extend(defaults, opts);

        //添加需要的样式
        if ($('head').find('#tree_table_style').length == 0) {
            $('head').append('<style type="text/css" id="tree_table_style">'
            + '.tree_table .node, .tree_table .active_node {width:16px;height:16px;border: medium none; margin: 0; padding: 0;display: inline-block;}\n'
            + '.tree_table .openImg {background:url(' + opts.openImg + ');}\n'
            + '.tree_table .shutImg {background:url(' + opts.shutImg + ');}\n'
            + '.tree_table .leafImg {background:url(' + opts.leafImg + ');}\n'
            + '.tree_table .lastOpenImg {background:url(' + opts.lastOpenImg + ');}\n'
            + '.tree_table .lastShutImg {background:url(' + opts.lastShutImg + ');}\n'
            + '.tree_table .lastLeafImg {background:url(' + opts.lastLeafImg + ');}\n'
            + '.tree_table .vertLineImg {background:url(' + opts.vertLineImg + ');}\n'
            + '.tree_table .blankImg {background:url(' + opts.blankImg + ');}\n'
            + '.tree_table .active_node {cursor: pointer;}</style>');
        }

        var pMap = {}, cMap = {};
        var $trs = this.find('tr');

        this.addClass('tree_table');
        //构造父子关系
        $trs.each(function (i) {
            var pId = $(this).attr('pId') || 0;
            pMap[pId] || (pMap[pId] = []);
            pMap[pId].push(this.id);
            cMap[this.id] = pId;
        });

        //标识父节点是否有孩子、是否最后一个节点
        $trs.each(function (i) {
            pMap[this.id] && $(this).attr('hasChild', true);
            var pArr = pMap[cMap[this.id]];
            if (pArr[0] == this.id) {
                $(this).attr('isFirstOne', true);
            } else {
                var prevId = 0;
                for (var i = 0; i < pArr.length; i++) {
                    if (pArr[i] == this.id) { break; }
                    prevId = pArr[i];
                }
                $(this).attr('prevId', prevId);
            }

            pArr[pArr.length - 1] == this.id && $(this).attr('isLastOne', true);
			formatNode(this.id);
        });

        this.click(function (event) {
            var target = event.target;
            if (target.nodeName == 'SPAN') {
                if ($(target).attr('class') == "active_node openImg" || $(target).attr('class') == "active_node lastOpenImg") {
                    var id = $(target).parents('tr')[0].id;
                    $(target).attr('class', ($(target).attr('class') == 'active_node openImg' ? 'active_node shutImg' : 'active_node lastShutImg'));

                    //关闭所有孩子的tr
                    shut(id);
                } else if ($(target).attr('class') == "active_node shutImg" || $(target).attr('class') == "active_node lastShutImg") {
                    var id = $(target).parents('tr')[0].id;
                    $(target).attr('class', ($(target).attr('class') == 'active_node shutImg' ? 'active_node openImg' : 'active_node lastOpenImg'));
                    //展开所有直属节点，根据图标展开子孙节点
                    open(id);
                }
            }
        });
        //递归关闭所有的孩子
        function shut(id) {
			
            if (pMap[id]) {
                for (var i = 0; i < pMap[id].length; i++) {
                    shut(pMap[id][i]);
                }
                $('tr[pId=' + id + ']').hide();
            }
        }
        //根据历史记录来展开节点
        function open(id) {
            $('tr[pId=' + id + ']').show();
            for (var i = 0; i < pMap[id].length; i++) {
                var cId = pMap[id][i];
                if (pMap[cId]) {
                    var className = $('#' + cId).find('.active_node').attr('class');
                    //如果子节点是展开图表的，则需要展开此节点
                    (className == 'active_node openImg' || className == 'active_node lastOpenImg') && open(cId);
                }
            }
        }

        function formatNode(id) {
            var $cur = $('#' + id);
            var $next = $cur.next();

            if (cMap[id] == 0) {
                //如果是顶级节点，则没有prev_span
                var $preSpan = $('<span class="prev_span"></span>');
            } else {
                //先判断是否有上一个兄弟节点
                if (!$cur.attr('isFirstOne')) {
                    var $preSpan = $('#' + $cur.attr('prevId')).children("td").eq(opts.column).find('.prev_span').clone();
                } else {
                    var $parent = $('#' + cMap[id]);
                    //没有上一个兄弟节点，则使用父节点的prev_span
                    var $preSpan = $parent.children("td").eq(opts.column).find('.prev_span').clone();

                    //如果父亲后面没有兄弟，则直接加空白，若有则加竖线
                    if ($parent.attr('isLastOne')) {
                        $preSpan.append('<span class="node blankImg"></span>');
                    } else {
                        $preSpan.append('<span class="node vertLineImg"></span>');
                    }
                }
            }

            if ($cur.attr('hasChild')) {
                //如果有下一个节点，并且下一个节点的父亲与当前节点的父亲相同，则说明该节点不是最后一个节点
                var className = 'active_node ' +  ($cur.attr('isLastOne') ? 'lastOpenImg' : 'openImg');
            } else {
                var className = 'node ' + ($cur.attr('isLastOne') ? 'lastLeafImg' : 'leafImg');
            }

            $cur.children("td").eq(opts.column).prepend('<span class="' + className + '"></span>').prepend($preSpan);
        };

        return this;
    };
	
})(jQuery);