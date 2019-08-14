Object.extend(Prototype.Browser, {
    IE6: (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) ? (Number(RegExp.$1) == 6 ? true : false) : false,
    IE7: (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) ? (Number(RegExp.$1) == 7 ? true : false) : false,
    IE8: (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) ? (Number(RegExp.$1) == 8 ? true : false) : false,
    IE9: (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) ? (Number(RegExp.$1) == 9 ? true : false) : false
});
if (!window.Kariboo) {
    window.Kariboo = {};
}
;
Kariboo.Shipping = Class.create({
    initialize: function (json) {
        this.settings = json;
        $$('label[for="s_method_kariboo_kariboo"]').first().insert({'after': $("kariboospots")});
        this.container = $("kariboospots");
        this.selectedspot = false;

        this.showSpotsLinkClick = this.showSpotsLinkClick.bind(this);
        this.resolveSettings = this.resolveSettings.bind(this);
        this.openModal = this.openModal.bind(this);
        this.openInline = this.openInline.bind(this);
        this.drawMap = this.drawMap.bind(this);
        this.generateHours = this.generateHours.bind(this);
        this.pinMarkers = this.pinMarkers.bind(this);
        this.showExtraInfo = this.showExtraInfo.bind(this);
        this.filterMarkers = this.filterMarkers.bind(this);
        this.clearMarkers = this.clearMarkers.bind(this);
        this.closeInfobox = this.closeInfobox.bind(this);
        this.selectSpot = this.selectSpot.bind(this);
        this.clickSpot = this.clickSpot.bind(this);
        this.karibooClose = this.karibooClose.bind(this);
        this.topClose = this.topClose.bind(this);

        this.imageOpen = {
            url: this.settings.base_url + 'skin/frontend/base/default/images/kariboo/marker_open.png',
            size: new google.maps.Size(45, 45),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(22, 45)
        };
        this.mapOptions = {
            zoom: 11,
            panControl: false,
            zoomControl: false,
            mapTypeControl: false,
            scaleControl: false,
            streetViewControl: false,
            overviewMapControl: false,
            center: new google.maps.LatLng(51, 4),
            styles: [
                {"featureType": "all", "elementType": "all", "stylers": [
                    {"saturation": -93},
                    {"lightness": 8}
                ]},
                {featureType: "poi", stylers: [
                    {visibility: "off"}
                ]}
            ]
        };
        this.infoboxOptions = {
            content: document.createElement("div"),
            disableAutoPan: false,
            maxWidth: 0,
            pixelOffset: new google.maps.Size(0, -10),
            zIndex: null,
            boxStyle: {
                width: "235px"
            },
            closeBoxURL: "",
            infoBoxClearance: new google.maps.Size(20, 20),
            isHidden: false,
            pane: "floatPane",
            enableEventPropagation: true
        };
        this.shape = {
            coord: [1, 1, 1, 45, 45, 45, 45, 1],
            type: 'poly'
        };
        if (typeof $(document).eventsBinded == "undefined") {
            this.bindEvents();
        }
        $(document).eventsBinded = true;
        if (this.settings.default_selected == 1) {
            $("s_method_kariboo_kariboo").checked = true;
        }
        //a spot is already suggested.
        if ($("karibooresult").innerHTML != "") {
            var json = $("karibooresult").innerHTML.evalJSON(true);
            this.selectSpot(json);
        }
    },
    bindEvents: function () {
        //click to open map
        $(document).on('click', "#showspots", function (event) {
            event.preventDefault();
            this.showSpotsLinkClick();
        }.bind(this));

        //click on the little icon to see more information
        $(document).on('click', ".infobtn", function (event) {
            event.preventDefault();
            this.showExtraInfo();
        }.bind(this));
        $(document).on('click', ".info", function (event) {
            event.preventDefault();
            this.showExtraInfo();
        }.bind(this));

        //click on the filter submit
        $(document).on('click', "#filter_submit", function (event) {
            event.preventDefault();
            this.filterMarkers();
        }.bind(this));

        $(document).on('keypress', ".kariboofilter", function (event) {
            if (event.keyCode == Event.KEY_RETURN || event.which == Event.KEY_RETURN) {
                this.filterMarkers();
                Event.stop(event);
            }
        }.bind(this));

        $(document).on('click', '.close-infoBox', function (event) {
            this.closeInfobox();
            event.preventDefault();
        }.bind(this));

        $(document).on('click', '.selectspot', function (event) {
            this.selectSpot(this.markers[Event.element(event).readAttribute("data-shopid")].json, this.json.days);
            event.preventDefault();
        }.bind(this));

        $(document).on('focusin', '#filter_postalcode', function () {
            this.postalCodeFocus();
        }.bind(this));

        $(document).on('focusout', '#filter_postalcode', function () {
            this.postalCodeBlur();
        }.bind(this));

        $(document).on('click', '.shoplistitem', function (event) {
            if (!event.target.hasClassName("selectspot")) {
                this.clickSpot((event.target.up('.shoplistitem') || event.target).id);
            }
        }.bind(this));

        $(document).on('click', '.karibooclose', function (event) {
            this.karibooClose();
            event.preventDefault();
        }.bind(this));
    },
    topClose: function () {
        if (this.settings.display == 1) {
            //callback will go tho karibooclose();
            this.modal.close();
        } else {
            this.karibooClose();
        }
    },
    karibooClose: function () {
        this.clearMarkers();
        $("karibooinfo").update("").setStyle({
            width: 'auto',
            height: 'auto'
        });

        $('showspots').style.display = 'inline';

        if (this.selectedspot === false) {
            $("karibooresult").style.display = "none";
        } else {
            $("karibooresult").style.display = "block";
        }

        this.map = null;
        this.firstMarker = null;
    },
    showSpotsLinkClick: function () {
        //hide button & current selected spot
        $("showspots").style.display = "none";
        $("karibooresult").style.display = "none";

        //hide the text for previous spot
        if (typeof $("kariboospots").down(".samespot", 0) != "undefined") {
            $("kariboospots").down(".samespot", 0).hide();
        }

        //start resolving setings
        this.resolveSettings();

        //already open the modal or inlinewindow
        if (this.settings.display == "1") {
            this.openModal();
        } else if (this.settings.display == "0") {
            this.openInline();
        }

        //place loader and show it
        $('map-canvas').update(this.html_loading);

        //AJAX!
        new Ajax.Request(this.settings.base_url + 'kariboo/ajax/getwindow', {
            method: 'get',
            requestHeaders: {Accept: 'application/json'},
            onSuccess: function (transport) {
                this.json = transport.responseText.evalJSON(true);

                if (this.json.error.length == 0) {
                    this.drawMap();
                    this.pinMarkers();
                } else {
                    alert(this.json.error);
                    this.topClose();
                }
            }.bind(this),
            onFailure: function () {
                alert("Could not contact the server , please try again");
                this.topClose();
            },
            onComplete: function () {
                $("kariboo_loading").style.display = "none";
            }.bind(this)
        });
    },
    selectSpot: function (json, days) {
        //spot selected
        var image;
        //fill form
        $$('input[name^=kariboo[spotid]]').first().value = json.ShopID;
        $$('input[name^=kariboo[street]]').first().value = json.ShopStreet + " " + json.ShopStreetNumber;
        $$('input[name^=kariboo[city]]').first().value = json.ShopCity;
        $$('input[name^=kariboo[postcode]]').first().value = json.ShopPostCode;
        $$('input[name^=kariboo[country]]').first().value = json.ShopCountry;
        $$('input[name^=kariboo[name]]').first().value = json.ShopName;
        $$('input[name^=kariboo[cod]]').first().value = json.COD;
        this.selectedspot = json.ShopID;

        //fill the infobox
        if (json.ShopPhoto) {
            image = json.ShopPhoto;
        } else {
            image = this.settings.imgpath + "kariboo.png";
        }

        var karibooresult = '<img src="' + image + '" alt="Kariboo!-spot" /><p><a href="#" class="info"><b>' + json.ShopName + '</b></a><a href="#" class="infobtn">?</a><br />' + json.ShopStreet + ' ' + json.ShopStreetNumber + '<br />' + json.ShopPostCode + ' ' + json.ShopCity + '</p><ul class="infobtnvw">' + this.generateHours(json, days);
        if (json.ShopAddressDescription !== "") {
            karibooresult += '<br />' + json.ShopAddressDescription;
        }
        karibooresult += '</ul>';
        $("karibooresult").update(karibooresult)
            .setStyle({
                display: 'block'
            });

        //if this is not true , it means that there was already a spot suggested.
        if (this.json) {
            //change link text
            $("showspots").update(this.settings.label_change);
            //close everything
            this.topClose();
        }

        //select spot after selection
        $("s_method_kariboo_kariboo").checked = true;

        //reset
        this.active_info = null;
        spotId = null;
    },
    closeInfobox: function () {
        if (this.active_info != null && this.infowindows[this.active_info])
            this.infowindows[this.active_info].close();

        this.active_info = null;
    },
    resolveSettings: function () {
        //if the width setting is empty apply full width
        if (this.settings.width) {
            this.mapwidth = this.settings.width + 'px';
        } else {
            this.mapwidth = window.document.documentElement.clientWidth;
        }

        //if the height setting is empty apply full width
        if (this.settings.height) {
            this.mapheight = this.settings.height + 'px';
            $$('label[for="s_method_kariboo_kariboo"]')
        } else {
            if (this.settings.display == 0) {
                this.mapheight = "600px";
            } else {
                this.mapheight = window.document.documentElement.clientHeight;
            }
        }

        this.filterLoading = false;

        //add html
        this.html_filter = '<div class="filter"><form action="/kariboo/ajax/filterspots" method="post" id="kariboospotsfilterform">' +
            '<div class="inputgroup postalcode"><input type="text" id="filter_postalcode" class="kariboofilter" name="filter_postalcode" placeholder="' + this.settings.label_postcode + '"/></div>' +
            '<div class="inputgroup"><label for="filter_openafter16">' + this.settings.label_openafter + '</label><input type="checkbox" class="kariboofilter" id="filter_openafter16" name="filter_openafter16" /></div>' +
            '<div class="inputgroup"><label for="filter_openonsunday">' + this.settings.label_opensund + '</label><input type="checkbox" class="kariboofilter" id="filter_openonsunday" name="filter_openonsunday" /></div>' +
            '<input type="submit" value="' + this.settings.label_filter + '" id="filter_submit" /></form></div>';
        this.html_close = '<a class="karibooclose">Close</a>';
        this.html_list = '<ul class="list" id="kariboolist"></ul>';
        this.html_map = '<div id="map-canvas" class="map"></div>';
        this.html_loading = '<div class="kariboo_loading"><div class="image"></div><span class="ajaxloading"></span><span class="kariboo-please-wait">' + this.settings.label_loading + '</span></div>';
        this.min_filter_width = 430;

        this.iecompat = Prototype.Browser.IE6 || Prototype.Browser.IE7 || Prototype.Browser.IE8 || Prototype.Browser.IE9;
    },
    openModal: function () {
        this.modal = new Window({
            id: 'kariboo_shipping_popup',
            className: 'kariboo_shipping_window',
            width: this.mapwidth,
            height: this.mapheight,
            minimizable: false,
            maximizable: false,
            showEffectOptions: {
                duration: 0.4
            },
            hideEffectOptions: {
                duration: 0.4
            },
            destroyOnClose: true
        });
        this.modal.setZIndex(100);
        this.modal.showCenter(true);
        this.modal.setCloseCallback(function () {
            this.karibooClose();
            return true;
        }.bind(this));

        if (this.settings.list == 1) {
            this.modal.setHTMLContent('<div class="mapcontainer">' + this.html_filter + this.html_map + this.html_list + '</div>');

            $("map-canvas").setStyle({
                width: this.settings.width - 220 + "px"
            });

            if ((this.settings.width - 320) >= this.min_filter_width && this.iecompat == false) {
                $$(".mapcontainer").first().down(".filter", 0)
                    .setStyle({
                        width: (this.settings.width - 320) + "px"
                    });
            } else {
                $$(".mapcontainer").first().down(".filter", 0).addClassName("response");
            }

        } else {
            this.modal.setHTMLContent('<div class="mapcontainer">' + this.html_filter + this.html_map + '</div>');
        }

        if (this.iecompat) {
            $('filter_postalcode').value = this.settings.label_postcode;
        }
    },
    openInline: function () {
        var mapcontainer = new Element("div")
            .addClassName("mapcontainer inline")
            .setStyle({
                width: this.mapwidth,
                height: this.mapheight
            })
            .insert(this.html_filter)
            .insert(this.html_map);
        var inlinemapwidth = this.settings.width;

        if (this.settings.list == 1) {
            $$('.kariboospotswrapper').first().addClassName('inline');

            inlinemapwidth = this.settings.width - 220;

            mapcontainer.insert(this.html_list)
                .down('.map', 0)
                .setStyle({
                    width: inlinemapwidth + "px"
                });
        }
        if ((inlinemapwidth - 100) >= this.min_filter_width && this.iecompat == false) {
            $("karibooinfo").update(mapcontainer.insert(this.html_close))
                .down(".filter", 0)
                .setStyle({
                    width: (inlinemapwidth - 100) + "px"
                });
        } else {
            $("karibooinfo").update(mapcontainer.insert(this.html_close))
                .down(".filter", 0)
                .addClassName("response");
        }

        //hide the link and result
        $("showspots").setStyle({
            display: 'none'
        });
        $("karibooresult").setStyle({
            display: 'none'
        });

        if (this.iecompat) {
            $('filter_postalcode').value = this.settings.label_postcode;
        }
    },
    postalCodeFocus: function () {
        if (this.iecompat) {
            if ($('filter_postalcode').value == this.settings.label_postcode) {
                $('filter_postalcode').value = '';
            }
        }
    },
    postalCodeBlur: function () {
        if (this.iecompat) {
            if ($('filter_postalcode').value == '') {
                $('filter_postalcode').value = this.settings.label_postcode;
            }
        }
    },
    drawMap: function () {
        this.map = null; //reset
        this.map = new google.maps.Map($('map-canvas'), this.mapOptions);
    },
    pinMarkers: function () {
        this.infowindows = {};
        this.markers = {};

        //loop trough shops
        for (var i = 0, LtLgLen = this.json.shops.length; i < LtLgLen; i++) {
            //google maps infobox
            this.infowindows[this.json.shops[i].ShopID] = new InfoBox(this.infoboxOptions);
            this.infowindows[this.json.shops[i].ShopID].setContent('<a href="#" class="close close-infoBox"></a>' +
                '<h3>' + this.json.shops[i].ShopName + '</h3>' +
                '<p>' + this.json.shops[i].ShopStreet + ' ' + this.json.shops[i].ShopStreetNumber +
                '<br />' + this.json.shops[i].ShopPostCode + ' ' + this.json.shops[i].ShopCity +
                '</p><ul class="hours">' + this.generateHours(this.json.shops[i], this.json.days) + '</ul>' +
                '<a href="#" data-shopid="' + this.json.shops[i].ShopID + '" class="selectspot">Select &raquo;</a>');

            //google maps marker
            this.markers[this.json.shops[i].ShopID] = new google.maps.Marker({
                position: new google.maps.LatLng(this.json.shops[i].ShopLatitude, this.json.shops[i].ShopLongitude),
                map: this.map,
                icon: this.imageOpen,
                shape: this.shape,
                zIndex: 1,
                json: this.json.shops[i]
            });

            google.maps.event.addListener(this.markers[this.json.shops[i].ShopID], 'click', (function (marker) {
                return function () {
                    this.clickSpot(marker.json.ShopID);
                }.bind(this)
            }.bind(this))(this.markers[this.json.shops[i].ShopID]));

            //center the map on the closest spot
            if (i == 0) {
                this.firstMarker = this.markers[this.json.shops[i].ShopID];
            }

            //list
            if (this.settings.list == 1) {
                $$('ul.list').first().insert("<li class='shoplistitem' id='" + this.json.shops[i].ShopID + "'>" + "<span class='title'>" + this.json.shops[i].ShopName + "</span>" + "<span class='address'>" + this.json.shops[i].ShopStreet + " " + this.json.shops[i].ShopStreetNumber + "</span>" + "<span class='city'>" + this.json.shops[i].ShopPostCode + " " + this.json.shops[i].ShopCity + "</span><a href='#' data-shopid='" + this.json.shops[i].ShopID + "' class='selectspot' >" + this.settings.label_select + "</a></li>");
            }
        }

        google.maps.event.addListenerOnce(this.map, 'idle', function () {
            google.maps.event.trigger(this.map, 'resize');
            google.maps.event.trigger(this.firstMarker, 'click');
        }.bind(this));
    },
    clickSpot: function (spotid) {
        //move map to center of this marker
        this.map.panTo(this.markers[spotid].getPosition());

        //update the list (if enabled)
        if (this.settings.list == 1) {
            var expanded = $$(".expanded").first();
            if (expanded != undefined) {
                expanded.removeClassName("expanded");
            }

            $$(".list").first().scrollTop = $(spotid).addClassName("expanded").offsetTop - 50;
        }

        //open the infobubble
        if (this.active_info != null) {
            this.infowindows[this.active_info].close();
        }
        this.infowindows[spotid].open(this.map, this.markers[spotid]);

        //active marker is this one
        this.active_info = spotid;
    },
    showExtraInfo: function () {
        if ($$(".infobtnvw").first().style.visibility == "visible") {
            $$(".infobtnvw").first().style.visibility = "hidden";
        } else {
            $$(".infobtnvw").first().setStyle({
                left: ($$(".infobtn").first().offsetLeft + $$(".infobtn").first().getDimensions().width) + "px",
                visibility: "visible"
            });
        }
    },
    clearMarkers: function () {
        for (var key in this.markers) {
            //remove marker from map
            this.markers[key].setMap(null);
            //remove infowindow
            this.infowindows[key].close();
            //remove item from list
            if (this.settings.list == 1) {
                if (key != null && typeof $(key) != "undefined") {
                    $(key).remove();
                }
            }
        }
        this.markers = {};
        this.active_info = null;
        this.infowindows = {};
    },
    generateHours: function (json, days) {
        //generate openinghours
        if (!json.days) {
            var jdls = days;
        }
        else {
            var jdls = json.days;
        }
        var hoursoutput = "";
        for (var datum in jdls) {
            var one = json.ShopOpeningHours["HD1" + datum];
            var two = json.ShopOpeningHours["HF1" + datum];
            var three = json.ShopOpeningHours["HD2" + datum];
            var four = json.ShopOpeningHours["HF2" + datum];

            one = one.slice(0, 2) + ":" + one.slice(2);
            two = two.slice(0, 2) + ":" + two.slice(2);
            three = three.slice(0, 2) + ":" + three.slice(2);
            four = four.slice(0, 2) + ":" + four.slice(2);

            if (one == "00:00" && two == "00:00") {
                one = "";
                two = "";
            }
            if (three == "00:00" && four == "00:00") {
                three = "";
                four = "";
            }
            hoursoutput = hoursoutput + "<li>" + "<span class='day'>" + jdls[datum] + "</span><span class='large'>" + one + '-' + two + "</span><span class='small'>/</span><span class='large'>" + three + '-' + four + "</span></li>";
        }

        return hoursoutput;
    },
    filterMarkers: function () {
        //disable a second click
        if (this.filterLoading != true) {

            this.filterLoading = true;
            $("filter_submit").addClassName("busy");

            if ($("filter_postalcode").value == this.settings.label_postcode) {
                $("filter_postalcode").value = "";
            }

            var parameters = "";
            if (this.settings.display == "1") {
                //overlay
                parameters = $("kariboospotsfilterform").serialize();
            } else if (this.settings.display == "0") {
                //inline
                parameters = $("co-shipping-method-form").serialize();
            }

            new Ajax.Request(this.settings.base_url + 'kariboo/ajax/getwindow', {
                method: 'post',
                parameters: parameters,
                requestHeaders: {Accept: 'application/json'},
                onSuccess: function (transport) {
                    var json = transport.responseText.evalJSON(true);
                    if (json.error.length == 0) {
                        this.clearMarkers();
                        this.json = json;
                        this.pinMarkers();
                        google.maps.event.trigger(this.map, 'resize');
                        google.maps.event.trigger(this.firstMarker, 'click');
                    } else {
                        alert(json.error);
                        this.topClose();
                    }
                }.bind(this),
                onFailure: function () {
                    alert("Could not contact the server , please try again");
                    this.topClose();
                },
                onComplete: function () {
                    //enable the button and reset the click
                    this.filterLoading = false;
                    $("filter_submit").removeClassName("busy");
                    this.postalCodeBlur();
                }.bind(this)
            });
        }
    }
});