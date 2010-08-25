/*
Tomas V.V.Cox <tvvcox@ossim.net>
BSD License
*/

// ticket functions
var cX = 0; var cY = 0; var rX = 0; var rY = 0;
var tick_now = false;
function UpdateCursorPosition(e){ cX = e.pageX; cY = e.pageY; ticket_pos();}
function UpdateCursorPositionDocAll(e){ cX = event.clientX; cY = event.clientY; ticket_pos(); }
if(document.all) { document.onmousemove = UpdateCursorPositionDocAll; }
else { document.onmousemove = UpdateCursorPosition; }

function ticket_pos() {
	if (document.getElementById('help'))
	{
		if (typeof(document.getElementById('help')) != 'undefined') {
			document.getElementById('help').style.left = (cX-150) + "px";
			document.getElementById('help').style.top = (cY+10) + "px";
		}
	}
}

if (!Control) var Control = {};

Control.Panel = {

    setOptions: function(opts) {
        if (!opts) opts = {};
        var defaults = {
            cols: 2,
            rows: 2,
            posClass: 'placeo',
            posHoverClass: 'active',
            posHeight: 100,
            posWidth: 400,
            // callback function called once the user dropped
            // a window over other position
            onWindowMove: false
        };

        this.options = {};
        for (var i in defaults) {
            this.options[i] = opts[i] ? opts[i] : defaults[i];
        };
    },
    /*
        Use this instead of Prototype::Element.getHeight(), as it is able
        to get the height of a "display: none" element
    */
    getH: function(element) {
        if (Element.getStyle(element, 'display') != 'none') {
            return element.offsetHeight;
        }
        // All *Width and *Height properties give 0 on elements with display none,
        // so enable the element temporarily
        var els = element.style;
        var originalVisibility = els.visibility;
        var originalPosition = els.position;
        els.visibility = 'hidden';
        els.position = 'absolute';
        els.display = 'block';
        var originalWidth = element.clientWidth;
        var originalHeight = element.clientHeight;
        // Here's the trick.. we activate (display: block) the element, resize
        // to the desired position width and then get the correct size
        // from the browser. After that, deactivate the element again.
        // Note: any need for resizing the element back?
        //Element.setStyle(element, {width: this.options.posWidth+'px'});
        var h = element.offsetHeight;
        els.display = 'none';
        els.position = originalPosition;
        els.visibility = originalVisibility;
        return h;
    },

    /*
     * @param pos string|object The position in the grid: ie "2x3"
     * @param el  string|object The html contents
     * @param optional height   The height of the contents if "el" is an html string
     *
     */
    setWindow: function(pos, el, height) {
        if (typeof el == 'string') {
            var h = height;
            var html = el;
        } else {
            var h = this.getH(el);
            var html = el.innerHTML;
        }
        $(pos).innerHTML = html;
        Element.setStyle(pos, {height: h+'px'});
    },

    getWindow: function(pos) {
        return $(pos).innerHTML;
    },

    /*
    Search the position of the window between the parents
    of the given element
    */
    destroyWindow: function(el) {
        var pos = this.getPlace(el);
        if (pos) {
            $(pos).innerHTML = '';
            Element.setStyle($(pos), {height: this.options.posHeight+'px'});
        }
    },

    moveWindow: function(fromPosEl, toPosEl) {
        // Trick for avoiding copy the object reference
        var from_html = fromPosEl.innerHTML;
        // For some reason Firefox is giving 10px extra
        var from_h = this.getH(fromPosEl) - 10;
        var to_h = this.getH(toPosEl) - 10;

        fromPosEl.innerHTML = toPosEl.innerHTML;
        toPosEl.innerHTML = from_html;

        Element.setStyle(fromPosEl, {height: to_h+'px'});
        Element.setStyle(toPosEl, {height: from_h+'px'});

		if (this.options.onWindowMove) {
			this.options.onWindowMove(fromPosEl, toPosEl);
		}
    },

    /*
        Recursive DOM Tree parent finder from
        a given child up to first pos window node
    */
    getPlace: function(el)
    {
        /*
        Node Types: (http://www.webreference.com/js/column102/2.html)
        1 - element node (nodeName = the HTML object)
        3 - text node (nodeName = '#text')
        9 - document  (nodeName = '#document')
        */
        if (!el.nodeType || el.parentNode.nodeType == 9) {
            alert("No se econtro"); return 0;
        }
        if (el.parentNode.nodeType != 1) {
            return 0;
        }
        //note: node.getAttribute('class') doesn't work with IE6
        var _class = el.parentNode.className;
        if (_class != this.options.posClass) {
            return this.getPlace(el.parentNode);
        }
        return el.parentNode;
    },

	getWindowName: function(fromNode)
	{
		var node = this.getPlace(fromNode);
		return node.id;
	},

    debug: function(str)
    {
        //document.write(str+'<br>');
        //sleep(1);
    },

    /*
        Recursive DOM Tree children node searching,
        starting from a given parent node
    */
    getNode: function(fromNode, id)
    {
        if (fromNode.nodeType != 1) {
            return 0;
        }
        if (fromNode.id == id) {
            return fromNode;
        }
        if (!fromNode.hasChildNodes()) {
            return 0;
        }
        var i = 0;
        var child = fromNode.childNodes[i];
        while (child) {
            var found = this.getNode(child, id);
            if (found) {
                return found;
            }
            i++;
            child = fromNode.childNodes[i];
        }
        return 0;
    },

    getNodeFromThisPanel: function(node, searchId)
    {
        var placeNode = this.getPlace(node);
        if (!placeNode) {
            return 'undefined';
        }
        alert(placeNode.id);
        var searchNode = this.getNode(placeNode, searchId);
        return searchNode;
    },

    drawGrid: function(atEl) {
        var i,j,h,w,id;
        var ids = new Array();
        var panel = tmpstyle = '';
        //var style = 'width: '+this.options.posWidth+'px;';
	var style='width:100%';
        panel += '<table width="1%" border="0" cellspacing="0" cellpading="0"><tr>';
        for (i = 1; i <= this.options.cols; i++) {
            panel += '<td valign="top">\n\t<table>\n';
            for (j = 1; j <= this.options.rows; j++) {
                panel += '\n\t<tr><td>';
                id = i+'x'+j;
                panel += '\t\t<div id="'+id+'" class="'+this.options.posClass+'" style="'+style+'">'+id+'</div>\n';
                ids.push(id);
                panel += '\n\t</td></tr>\n';
            }
            panel += '\n\t</table>\n</td>';
        }
        panel += '</tr></table>';
        atEl.innerHTML = panel;
        ids.each(function(v, k) {
            new Draggable(v, {revert:true});
            Droppables.add(v, {
                accept: Control.Panel.options.posClass,
                hoverclass: Control.Panel.options.posHoverClass,
                onDrop:function(fromPosEl, toPosEl) {
                    Control.Panel.moveWindow(fromPosEl, toPosEl);
                }
            });
        });
        //alert(atEl.innerHTML);
    }
}

/*
(code idea from the DojoToolKit.org ColorPalette widget)

Usage:

Public properties:

1) Control.ColorPalette.show(element): Assigns the HTML code of the ColorPalette
to the $(element).innerHTML (and does a Element.show(element))

2) Control.ColorPalette.toggle(element): Shows the palette if it was hidden
and viceversa (handy for onClick events).

3) Control.ColorPalette.registerOnColorClick: This property stores the function
that should be called when the user clicks on a color of the Palette.  That callback
function should accept just one param the color in the form: "#ffffff".

Ex:

<script>
function colorSelected(color)
{
    alert(color);
}
Control.ColorPalette.registerOnColorClick = colorSelected;
</script>

4) Control.ColorPalette.palette: This property sets the number of colors shown in
the palette (currently valid values are: "7x10", "3x4" (defaults to "7x10"). You
can define your own palettes. Common colors list at
http://members.tripod.com/p_cole/public/color.html

*/

Control.ColorPalette = {

    palette: "7x10",

    palettes: {
        "7x10": [["fff", "fcc", "fc9", "ff9", "ffc", "9f9", "9ff", "cff", "ccf", "fcf"],
            ["ccc", "f66", "f96", "ff6", "ff3", "6f9", "3ff", "6ff", "99f", "f9f"],
            ["c0c0c0", "f00", "f90", "fc6", "ff0", "3f3", "6cc", "3cf", "66c", "c6c"],
            ["999", "c00", "f60", "fc3", "fc0", "3c0", "0cc", "36f", "63f", "c3c"],
            ["666", "900", "c60", "c93", "990", "090", "399", "33f", "60c", "939"],
            ["333", "600", "930", "963", "660", "060", "366", "009", "339", "636"],
            ["000", "300", "630", "633", "330", "030", "033", "006", "309", "303"]],

        "3x4": [["ffffff"/*white*/, "00ff00"/*lime*/, "008000"/*green*/, "0000ff"/*blue*/],
            ["c0c0c0"/*silver*/, "ffff00"/*yellow*/, "ff00ff"/*fuchsia*/, "000080"/*navy*/],
            ["808080"/*gray*/, "ff0000"/*red*/, "800080"/*purple*/, "000000"/*black*/]]
            //["00ffff"/*aqua*/, "808000"/*olive*/, "800000"/*maroon*/, "008080"/*teal*/]];
    },
    show: function(el) {
        var colors = this.palettes[this.palette];
        var html, style, color;
        html = '<table cellpadding="0" cellspacing="1" border="1" style="background-color: white">';
        for (var i = 0; i < colors.length; i++) {
            html += '<tr>';
            for (var j = 0; j < colors[i].length; j++) {
                // convert 3 letters color in 6 letters color: fc9 -> ffcc99
                if (colors[i][j].length == 3) {
                    colors[i][j] = colors[i][j].replace(/(.)(.)(.)/, "$1$1$2$2$3$3");
				}
                color = '#'+colors[i][j];
                style =  'border: 1px gray solid; background-color: '+color+'; ';
                style += 'width: 15px; height: 20px; font-size: 1px;';
                html += '<td style="'+style+'" ';
                html += '    onmouseover="javascript: Element.setStyle(this, {borderColor: \'white\'});"';
                html += '    onmouseout="javascript: Element.setStyle(this, {borderColor: \'gray\'});"';
                html += '    onClick="javascript: Control.ColorPalette.onColorClick(\''+color+'\');">';
                html += '&nbsp</td>';
            }
            html += '</tr>';
        }
        html += '</table>';
        $(el).innerHTML = html;
        Element.show(el);
    },
    toggle: function(el) {
        if (!Element.visible(el)) {
            Control.ColorPalette.show(el);
        } else {
            Element.hide(el);
        }
    },
    onColorClick: function(color) {
        this.registerOnColorClick(color);
    },
    // This will be called when a color is selected
    registerOnColorClick: function(color) {
    }
}

Control.Tip = {
    use: 'help',
    show: function(msg) {
        if (msg) {
			tick_now = true;
			Element.show($(this.use));
            $(this.use).innerHTML = msg;
        }
    },
    hide: function() {
        tick_now = false;
		Element.hide($(this.use));
    }
}
