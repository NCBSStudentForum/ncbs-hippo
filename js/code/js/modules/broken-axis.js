/*
 Highcharts JS v5.0.4 (2016-11-25)

 (c) 2009-2016 Torstein Honsi

 License: www.highcharts.com/license
*/
(function(h){"object"===typeof module&&module.exports?module.exports=h:h(Highcharts)})(function(h){(function(f){function h(){return Array.prototype.slice.call(arguments,1)}function u(g){g.apply(this);this.drawBreaks(this.xAxis,["x"]);this.drawBreaks(this.yAxis,r(this.pointArrayMap,["y"]))}var r=f.pick,p=f.wrap,t=f.each,x=f.extend,v=f.fireEvent,q=f.Axis,y=f.Series;x(q.prototype,{isInBreak:function(g,c){var d=g.repeat||Infinity,b=g.from,a=g.to-g.from;c=c>=b?(c-b)%d:d-(b-c)%d;return g.inclusive?c<=a:
c<a&&0!==c},isInAnyBreak:function(g,c){var d=this.options.breaks,b=d&&d.length,a,e,m;if(b){for(;b--;)this.isInBreak(d[b],g)&&(a=!0,e||(e=r(d[b].showPoints,this.isXAxis?!1:!0)));m=a&&c?a&&!e:a}return m}});p(q.prototype,"setTickPositions",function(g){g.apply(this,Array.prototype.slice.call(arguments,1));if(this.options.breaks){var c=this.tickPositions,d=this.tickPositions.info,b=[],a;for(a=0;a<c.length;a++)this.isInAnyBreak(c[a])||b.push(c[a]);this.tickPositions=b;this.tickPositions.info=d}});p(q.prototype,
"init",function(g,c,d){d.breaks&&d.breaks.length&&(d.ordinal=!1);g.call(this,c,d);if(this.options.breaks){var b=this;b.isBroken=!0;this.val2lin=function(a){var e=a,m,d;for(d=0;d<b.breakArray.length;d++)if(m=b.breakArray[d],m.to<=a)e-=m.len;else if(m.from>=a)break;else if(b.isInBreak(m,a)){e-=a-m.from;break}return e};this.lin2val=function(a){var e,d;for(d=0;d<b.breakArray.length&&!(e=b.breakArray[d],e.from>=a);d++)e.to<a?a+=e.len:b.isInBreak(e,a)&&(a+=e.len);return a};this.setExtremes=function(a,b,
d,g,c){for(;this.isInAnyBreak(a);)a-=this.closestPointRange;for(;this.isInAnyBreak(b);)b-=this.closestPointRange;q.prototype.setExtremes.call(this,a,b,d,g,c)};this.setAxisTranslation=function(a){q.prototype.setAxisTranslation.call(this,a);var e=b.options.breaks;a=[];var d=[],g=0,c,k,n=b.userMin||b.min,f=b.userMax||b.max,l,h;for(h in e)k=e[h],c=k.repeat||Infinity,b.isInBreak(k,n)&&(n+=k.to%c-n%c),b.isInBreak(k,f)&&(f-=f%c-k.from%c);for(h in e){k=e[h];l=k.from;for(c=k.repeat||Infinity;l-c>n;)l-=c;for(;l<
n;)l+=c;for(;l<f;l+=c)a.push({value:l,move:"in"}),a.push({value:l+(k.to-k.from),move:"out",size:k.breakSize})}a.sort(function(a,b){return a.value===b.value?("in"===a.move?0:1)-("in"===b.move?0:1):a.value-b.value});e=0;l=n;for(h in a)k=a[h],e+="in"===k.move?1:-1,1===e&&"in"===k.move&&(l=k.value),0===e&&(d.push({from:l,to:k.value,len:k.value-l-(k.size||0)}),g+=k.value-l-(k.size||0));b.breakArray=d;v(b,"afterBreaks");b.transA*=(f-b.min)/(f-n-g);b.min=n;b.max=f}}});p(y.prototype,"generatePoints",function(g){g.apply(this,
h(arguments));var c=this.xAxis,d=this.yAxis,b=this.points,a,e=b.length,f=this.options.connectNulls,w;if(c&&d&&(c.options.breaks||d.options.breaks))for(;e--;)a=b[e],w=null===a.y&&!1===f,w||!c.isInAnyBreak(a.x,!0)&&!d.isInAnyBreak(a.y,!0)||(b.splice(e,1),this.data[e]&&this.data[e].destroyElements())});f.Series.prototype.drawBreaks=function(g,c){var d=this,b=d.points,a,e,f,h;t(c,function(c){a=g.breakArray||[];e=g.isXAxis?g.min:r(d.options.threshold,g.min);t(b,function(b){h=r(b["stack"+c.toUpperCase()],
b[c]);t(a,function(a){f=!1;if(e<a.from&&h>a.to||e>a.from&&h<a.from)f="pointBreak";else if(e<a.from&&h>a.from&&h<a.to||e>a.from&&h>a.to&&h<a.from)f="pointInBreak";f&&v(g,f,{point:b,brk:a})})})})};p(f.seriesTypes.column.prototype,"drawPoints",u);p(f.Series.prototype,"drawPoints",u)})(h)});
