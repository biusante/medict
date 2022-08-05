'use strict';

/**
 * Toolkit for ajax forms
 */
const Formajax = function() {
    /** Message send to a callback loader to say end of file */
    const EOF = '\u000A';
    /** Used as a separator between mutiline <div> */
    const LF = '&#10;';
    /** {HTMLFormElement} form with params to send for queries like conc */
    var form = false;

    /**
     * Get URL and send line by line to a callback function.
     * “Line” separator could be configured with any string,
     * this allow to load multiline html chunks 
     * 
     * @param {String} url 
     * @param {function} callback 
     * @returns 
     */
    function loadLines(url, callback, sep = '\n') {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            var start = 0;
            xhr.onprogress = function() {
                // loop on separator
                var end;
                while ((end = xhr.response.indexOf(sep, start)) >= 0) {
                    callback(xhr.response.slice(start, end));
                    start = end + sep.length;
                }
            };
            xhr.onload = function() {
                let part = xhr.response.slice(start);
                if (part.trim()) callback(part);
                // last, send a message to callback
                callback(EOF);
                resolve();
            };
            xhr.onerror = function() {
                reject(Error('Connection failed'));
            };
            xhr.responseType = 'text';
            xhr.open('GET', url);
            xhr.send();
        });
    }


    /**
     * Load Json complete, not in slices
     * @param {*} url 
     * @param {*} callback 
     */
    function loadJson(url, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.responseType = 'json';
        xhr.onload = function() {
            var status = xhr.status;
            if (status === 200) {
                callback(xhr.response, null);
            } else { // in case of error ?
                callback(xhr.response, status);
            }
        };
        xhr.send();
    }

    /**
     * Get form values as url pars
     */
    function pars(include, exclude) {
        // ensure array
        if (!include);
        else if (!Array.isArray(include)) include = [include];
        if (!exclude);
        else if (!Array.isArray(exclude)) exclude = [exclude];


        const formData = new FormData(form);
        // delete empty values for nice minimal url
        // take a copy of keys, formData.keys will change
        const keys = Array.from(formData.keys());
        for (const key of keys) {
            if (include && !include.find(k => k === key)) {
                formData.delete(key);
                continue;
            }
            if (exclude && exclude.find(k => k === key)) {
                formData.delete(key);
                continue;
            }
            // 1) delete, 2) append non empty
            let values = formData.getAll(key);
            formData.delete(key);
            const len = values.length;
            if (len < 1) continue;
            for (let i = 0; i < len; i++) {
                if (!values[i]) continue;
                formData.append(key, values[i]);
            }
        }
        return new URLSearchParams(formData);
    }

    /**
     * Intitialize an input with suggest
     * @param {HTMLInputElement} input 
     * @returns 
     */
    function suggestInit(input) {
        if (!input) {
            console.log("[Formajax] No <input> to equip");
            return;
        }
        if (input.list) { // create a list
            console.log("[Formajax] <datalist> is bad for filtering\n" + input);
        }
        if (!input.dataset.url) {
            console.log("[Formajax] No @data-url to get data from\n" + input);
            return;
        }
        if (!input.dataset.name) {
            console.log("[Formajax] No @data-name to create params\n" + input);
            return;
        }
        input.autocomplete = 'off';
        // create suggest
        const suggest = document.createElement("div");
        suggest.className = "suggest " + input.dataset.name;
        input.parentNode.insertBefore(suggest, input.nextSibling);
        input.suggest = suggest;
        suggest.input = input;
        suggest.hide = suggestHide;
        suggest.show = suggestShow;
        // global click hide current suggest
        window.addEventListener('click', (e) => {
            if (window.suggest) window.suggest.hide();
        });
        // click in suggest, avoid hide effect at body level
        input.parentNode.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        // control suggests, 
        input.addEventListener('click', function(e) {
            if (suggest.style.display != 'block') {
                suggest.show();
            } else {
                suggest.hide();
            }
        });

        input.addEventListener('click', suggestFill);
        input.addEventListener('input', suggestFill);
        input.addEventListener('input', function(e) { suggest.show(); });

        suggest.addEventListener("touchstart", function(e) {
            // si on défile la liste de résultats sur du tactile, désafficher le clavier
            input.blur();
        });
        input.addEventListener('keyup', function(e) {
            e = e || window.event;
            if (e.key == 'Esc' || e.key == 'Escape') {
                suggest.hide();
            } else if (e.key == 'Backspace') {
                if (input.value) return;
                suggest.hide();
            } else if (e.key == 'ArrowDown') {
                if (input.value) return;
                suggest.show();
            } else if (e.key == 'ArrowUp') {
                // focus ?
            }
        });
    }



    /**
     * Append a line record to a suggest
     * @param {HTMLDivElement} suggest block where to append suggestions 
     * @param {*} line 
     */
    function suggestLine(suggest, json) {
        if (!json.trim()) { // sometimes empty
            return;
        }
        try {
            var data = JSON.parse(json);
        } catch (err) {
            console.log(Error('parsing: "' + json + "\"\n" + err));
            return;
        }
        // maybe meta
        if (!data.text || !data.id) {
            return;
        }

        let facet = document.createElement('div');
        facet.className = "facet";
        const hits = (data.hits) ? " (" + data.hits + ")" : "";
        if (data.html) {
            facet.innerHTML = data.html + hits;
        } else if (data.text) {
            facet.innerHTML = data.text + hits;
        } else { // ?? bad !
            facet.innerHTML = data.id + hits;
        }
        facet.dataset.id = data.id;
        facet.addEventListener('click', facetPush);
        facet.input = suggest.input;
        suggest.appendChild(facet);
    }

    /**
     * Start population of a suggester 
     * @param {Event} e 
     */
    function suggestFill(e) {
        const input = e.currentTarget;
        const suggest = input.suggest;
        // get forms params
        const formData = new FormData(input.form);
        const pars = new URLSearchParams(formData);
        pars.set("glob", input.value); // add the suggest query

        // search form sender and receiver
        const url = input.dataset.url + "?" + pars;
        suggest.innerText = '';
        loadLines(url, function(json) {
            suggestLine(suggest, json);
        });
    }

    /**
     * Delete an hidden field
     * @param {Event} e 
     */
    function inputDel(e) {
        const label = e.currentTarget.parentNode;
        label.parentNode.removeChild(label);
        update(true);
    }

    /**
     * Push a value for a facet
     * @param {Event} e 
     */
    function facetPush(e) {
        const facet = e.currentTarget;
        const label = document.createElement("label");
        label.className = 'facet';
        const a = document.createElement("a");
        a.innerText = '🞭';
        a.className = 'inputDel';
        a.addEventListener('click', inputDel);
        label.appendChild(a);
        const input = document.createElement("input");
        input.name = facet.input.dataset.name;
        input.type = 'hidden';
        input.value = facet.dataset.id;
        label.appendChild(input);
        const text = document.createTextNode(facet.textContent.replace(/ *\(\d+\) *$/, ''));
        label.appendChild(text);
        facet.input.parentNode.insertBefore(label, facet.input);
        facet.input.focus();
        facet.input.suggest.hide();
        update(true); // update interface
    }

    /**
     * Attached to a suggest pannel, hide
     */
    function suggestHide() {
        const suggest = this;
        suggest.blur();
        suggest.style.display = 'none';
        suggest.input.value = '';
        window.suggest = null;
    }

    /**
     * Attached to a suggest pannel, show
     */
    function suggestShow() {
        const suggest = this;
        if (window.suggest && window.suggest != suggest) {
            window.suggest.hide();
        }
        window.suggest = suggest;
        suggest.style.display = 'block';
    }

    /**
     * 
     * @param {*} form 
     * @returns 
     */
    function init(el) {
        if (!el) {
            console.log('[FormAjax] A <form> is required to init FormAjax');
            return;
        }
        form = el;
    }

    /**
     * Send query to populate concordance
     * @param {boolean} append 
     */
    function divLoad(id, append = false) {
        let div = document.getElementById(id);
        if (!div) return; // disappeared ?
        if (div.loading) return; // still loading
        div.loading = true;
        if (!append) {
            div.innerText = '';
        }
        let url = div.dataset.url + "?" + pars();
        loadLines(url, function(html) {
            insLine(div, html);
        }, LF);
    }

    /**
     * Append a record to a div
     * @param {*} html 
     * @returns 
     */
    function insLine(div, html) {
        if (!div) { // what ?
            return false;
        }
        // last line, liberate div for next load
        if (html == EOF) {
            div.loading = false;
            return;
        }
        div.insertAdjacentHTML('beforeend', html);
    }



    return {
        init: init,
        inputDel: inputDel,
        loadLines: loadLines,
        insLine: insLine,
        LF: LF,
        pars: pars,
        divLoad: divLoad,
        suggestInit: suggestInit,
    }
}();

(function() {

    /**
     * Double slider for dates
     */
    const Bislide = function() {
        function init() {
            // Initialize Sliders
            let els = document.getElementsByClassName("bislide");
            for (let x = 0; x < els.length; x++) {
                let sliders = els[x].getElementsByTagName("input");
                let slider1;
                let slider2;
                for (let y = 0; y < sliders.length; y++) {
                    if (sliders[y].type !== "range") continue;
                    if (!slider1) {
                        slider1 = sliders[y];
                        continue;
                    }
                    slider2 = sliders[y];
                    break;
                }
                if (!slider2) continue;
                els[x].values = els[x].getElementsByClassName("values")[0];
                els[x].slider1 = slider1;
                els[x].slider1.oninput = Bislide.input;
                els[x].slider1.onchange = Bislide.change;
                els[x].slider2 = slider2;
                els[x].slider2.oninput = Bislide.input;
                els[x].slider2.onchange = Bislide.change;
                slider2.oninput();
            }
        }

        function input() {
            // Get slider values
            var parent = this.parentNode;
            var val1 = parseFloat(parent.slider1.value);
            var val2 = parseFloat(parent.slider2.value);
            // swap value if needed 
            if (val1 > val2) {
                parent.slider1.value = val2;
                parent.slider2.value = val1;
            }
            // display
            if (!parent.values) return;
            parent.values.innerHTML = parent.slider1.value + " – " + parent.slider2.value;
        }
        /**
         * default action on change, submit form
         */
        function change(e) {
            const input = e.target;
            // submit form
            input.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
        }
        return {
            init: init,
            input: input,
            change: change,
        }
    }();
    // bottom script
    Bislide.init();

    /**
     * Pilot of Medit app
     */
    const Medict = function() {
        /* the form */
        var form;
        /* the viewer */
        var pageViewer;
        /** image to update for viewer */
        var viewmage;


        function init() {
            // init the form
            form = document.forms['medict'];
            if (!form) return;

            titresInit();

            Formajax.init(form);
            // prevent submit before afect it as event
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                motsLoad();
                Formajax.divLoad('entrees');
                return false;
            }, true);
            // send submit when suggest change
            form.q.addEventListener('input', (e) => {
                form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
            }, true);

            window.onpopstate = function(e) {
                var state = e.state;
                // a state produced by the app
                if (state !== null) {
                    winload();
                }
            };
            winload();

            // for efficiency, put a click event on the terme container (not all termes)
            const mots = document.getElementById('mots');
            if (mots) mots.addEventListener('click', motsClick);
            const entrees = document.getElementById('entrees');
            if (entrees) entrees.addEventListener('click', entreesClick);
            const sugg = document.getElementById('sugg');
            if (sugg) {
                sugg.addEventListener('click', entreesClick);
                sugg.addEventListener('click', suggClick);
            }
            setViewer('viewcont');
        }

        function titresInit() {
            const open = document.getElementById('titres_open');
            if (!open) {
                console.log('[Medict] formulaires, #titres_open introuvable');
                return;
            }
            const modal = document.getElementById('titres_modal');
            if (!modal) {
                console.log('[Medict] formulaires, #titres_modal introuvable');
                return;
            }

            const close = modal.querySelector('.close');
            if (!close) {
                // if no close no open
                console.log('[Medict] formulaire, bouton fermer popup introuvable : #titres_modal .close');
                return;
            }
            const modalEsc = function(e) {
                const key = e.key; // Or const {key} = event; in ES6+
                if (key !== "Escape") return;
                document.removeEventListener("keydown", modalEsc);
                modal.style.display = "none";
            };
            open.addEventListener('click', (e) => {
                modal.style.display = "block";
                document.addEventListener("keydown", modalEsc);
            }, true);
            close.addEventListener('click', (e) => {
                document.removeEventListener("keydown", modalEsc);
                modal.style.display = "none";
            }, true);
            // click outside modal close it
            modal.addEventListener('click', function(e) {
                if (e.target !== modal) return;
                document.removeEventListener("keydown", modalEsc);
                modal.style.display = "none";
            }, true);
            // count checked checkboxes
            let checkeds = modal.querySelectorAll('input[type="checkbox"]:checked').length;
            // Changement dans le formulaire
            const titreChange = function(e) {
                    if (this.checked) {
                        this.parentNode.classList.add("checked");
                    } else {
                        this.parentNode.classList.remove("checked");
                    }
                    // update URL but do not add entry in history
                    const url = new URL(window.location);
                    url.search = Formajax.pars();
                    window.history.replaceState({}, '', url);
                    // submit form
                    this.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
                }
                // loop on all checkbox
            const ticklist = modal.querySelectorAll("input[type=checkbox][name=cote]");
            for (let i = 0; i < ticklist.length; ++i) {
                const checkbox = ticklist[i];
                if (checkbox.checked) {
                    checkbox.parentNode.classList.add("checked");
                }
                checkbox.addEventListener('change', titreChange);
            }
            const coteAll = document.getElementById("coteAll");
            coteAll.addEventListener("change", function(e) {
                const flag = this.checked;
                // all or none, exclude url par
                // update URL but do not add entry in history
                const url = new URL(window.location);
                url.search = Formajax.pars(null, ['cote']);
                window.history.replaceState({}, '', url);
                if (flag) {
                    document.getElementById('coteAllCheck').style.display = 'none';
                    document.getElementById('coteAllUncheck').style.display = 'block';
                } else {
                    document.getElementById('coteAllCheck').style.display = 'block';
                    document.getElementById('coteAllUncheck').style.display = 'none';
                }
                for (let i = 0; i < ticklist.length; ++i) {
                    const checkbox = ticklist[i];
                    checkbox.checked = flag;
                    if (flag) checkbox.parentNode.classList.add("checked");
                    else checkbox.parentNode.classList.remove("checked");
                }
                // submit form
                this.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
            });
        }

        /**
         * update interface onload or with back history
         */
        function winload() {
            // TODO, update title filter, mutiple

            // update form with 
            (new URL(window.location.href)).searchParams.forEach(function(value, key) {
                if (!form[key]) return;
                form[key].value = value;
            });
            Formajax.divLoad('mots');
            Formajax.divLoad('entrees');
            Formajax.divLoad('sugg');
            // équiper les suggesteurs
            const inputs = document.querySelectorAll("input.multiple[data-url]");
            for (let i = 0; i < inputs.length; i++) {
                Formajax.suggestInit(inputs[i]);
            }


        }


        /**
         * Update interface ?
         * @returns 
         */
        function motsLoad() {
            Formajax.divLoad('mots');
            // update URL but do not add entry in history
            const url = new URL(window.location);
            url.search = Formajax.pars();
            window.history.replaceState({}, '', url);
        }


        function setViewer(id) {
            const div = document.getElementById(id);
            if (!div) return;
            let els = div.getElementsByTagName('img');
            if (!els || els.length < 1) return;
            viewmage = els[0];


            pageViewer = new Viewer(div, {
                transition: false,
                inline: true,
                navbar: 0,
                // minWidth: '100%', 
                toolbar: {
                    width: function() {
                        let cwidth = div.offsetWidth;
                        let iwidth = pageViewer.imageData.naturalWidth;
                        let zoom = cwidth / iwidth;
                        pageViewer.zoomTo(zoom);
                        pageViewer.moveTo(0, pageViewer.imageData.y);
                    },
                    zoomIn: true,
                    zoomOut: true,
                    oneToOne: true,
                    reset: true,
                },
                title: function(image) {
                    return null;
                },
                viewed() {
                    // default zoom on load, image width
                    let cwidth = div.offsetWidth;
                    let iwidth = pageViewer.imageData.naturalWidth;
                    let zoom = cwidth / iwidth;
                    pageViewer.zoomTo(zoom);
                    pageViewer.moveTo(0, 0);
                },
                zoomed() {
                    // record last Zoom level
                    pageViewer.lastZoomRequested = pageViewer.imageData.ratio;
                }
            });
            // viewer override of resize
            Viewer.prototype.resize = function() {
                var _this3 = this;

                if (!this.isShown || this.hiding) {
                    return;
                }

                if (this.fulled) {
                    this.close();
                    this.initBody();
                    this.open();
                }

                this.initContainer();
                this.initViewer();
                this.renderViewer();
                this.renderList();

                if (this.viewed) {
                    // do not resize image
                    /*
                    this.initImage(function() {
                        _this3.renderImage();
                    });
                    _this3.options.viewed();
                    */
                }

                if (this.played) {
                    if (this.options.fullscreen && this.fulled && !(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement)) {
                        this.stop();
                        return;
                    }

                    forEach(this.player.getElementsByTagName('img'), function(image) {
                        addListener(image, EVENT_LOAD, _this3.loadImage.bind(_this3), {
                            once: true
                        });
                        dispatchEvent(image, EVENT_LOAD);
                    });
                }
            };

            Viewer.prototype.wheel = function(event) {
                var _this4 = this;
                if (!this.viewed) {
                    return;
                }

                event.preventDefault(); // Limit wheel speed to prevent zoom too fast

                if (this.wheeling) {
                    return;
                }

                this.wheeling = true;
                setTimeout(function() {
                    _this4.wheeling = false;
                }, 50);
                var ratio = Number(this.options.zoomRatio) || 0.1;
                var delta = 1;

                if (event.deltaY) {
                    delta = event.deltaY;
                } else if (event.wheelDelta) {
                    delta = -event.wheelDelta;
                } else if (event.detail) {
                    delta = event.detail;
                }
                this.move(0, -delta);
            };
        }

        /**
         * Behavior of suggested term
         * @param {*} e 
         * @returns 
         */
        function suggClick(e) {
            let a = selfOrAncestor(e.target, 'a');
            if (!a) return;
            if (!a.classList.contains('sugg')) return;
            e.preventDefault();
            const pars = new URLSearchParams(a.search);
            const q = pars.get('q');
            if (!q) return;
            form.q.value = q;
            // form.t.value = q; // non juste l’index
            Formajax.divLoad('mots');
            window.history.pushState({}, window.title, window.location);

        }
        /**
         * Behavior of entries
         * @param {*} e 
         * @returns 
         */
        function entreesClick(e) {
            let a = selfOrAncestor(e.target, 'a');
            if (!a) return;
            if (!a.classList.contains('entree')) return;
            // https://www.biusante.parisdescartes.fr/iiif/2/bibnum:45674x04:%%/full/full/0/default.jpg
            // https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=pharma_019428x01&amp;p=444"
            // https://www.biusante.parisdescartes.fr/iiif/2/bibnum:47661x59:0122/0,512,512,512/512,/0/default.jpg
            let found = a.search.match(/cote=([^&]*)/);
            if (!found) return; // url error ?
            // seems we can prevent default now
            e.preventDefault();
            const cote = found[1];
            found = a.search.match(/p=([^&]*)/);
            if (!found) return; // url error ?
            const page = found[1];
            let p = pad(page, 4);
            const url = 'https://www.biusante.parisdescartes.fr/iiif/2/bibnum:' + cote + ':' + p + '/full/full/0/default.jpg';
            image.src = url;

            let link = document.getElementById('medica-ext');
            let html = a.html; // if prev / next link, see down
            if (!html) html = a.innerHTML;
            found = html.match(/p\.[  ](\d+)/);
            let folio = null;
            if (found) folio = parseInt(found[1]);
            if (link) {
                link.innerHTML = html;
                link.href = a.href;
            }
            link = document.getElementById('medica-prev');
            if (link) {
                p = parseInt(page) - 1;

                let href = a.href.replace(/p=([^&]*)/, 'p=' + p);
                link.href = href;
                let ht = html;
                if (folio) ht = ht.replace(/p\.[  ]\d+/, 'p. ' + (folio - 1));
                link.html = ht;
                link.onclick = entreesClick;
            }
            link = document.getElementById('medica-next');
            if (link) {
                p = parseInt(page) + 1;
                let href = a.href.replace(/p=([^&]*)/, 'p=' + p);
                link.href = href;
                let ht = html;
                if (folio) ht = ht.replace(/p\.[  ]\d+/, 'p. ' + (folio + 1));
                link.html = ht;
                link.onclick = entreesClick;
            }



            pageViewer.update();
            // change class
            if (document.lastEntree) document.lastEntree.classList.remove('active');
            if (a.classList.contains("active")) {
                a.classList.remove('active');
            } else {
                document.lastEntree = a;
                a.classList.add('active');
            }
        }

        function pad(num, width) {
            var s = "000000000" + num;
            return s.substring(s.length - width);
        }

        function selfOrAncestor(el, name) {
            while (el.tagName.toLowerCase() != name) {
                el = el.parentNode;
                if (!el) return false;
                let tag = el.tagName.toLowerCase();
                if (tag == 'div' || tag == 'nav' || tag == 'body') return false;
            }
            return el;
        }

        /**
         * When click in mots, do things
         */
        function motsClick(e) {
            e.preventDefault();
            // catch a link inside column of terms
            let a = selfOrAncestor(e.target, 'a');
            if (!a) return;
            const pars = new URLSearchParams(a.search);
            const terme = pars.get('t');

            // push history
            form['t'].value = terme;
            const url = new URL(window.location);
            url.search = Formajax.pars();
            window.history.pushState({}, terme, url);


            // Update frames
            Formajax.divLoad('entrees');
            Formajax.divLoad('sugg');
            // change class
            if (document.lastIndex) document.lastIndex.classList.remove('active');
            if (a.classList.contains("active")) {
                a.classList.remove('active');
            } else {
                document.lastIndex = a;
                a.classList.add('active');
            }
        }
        return {
            init: init,
        }
    }();
    Medict.init();
    const splitH = Split(['#col1', '#col2', '#col3'], {
        sizes: [20, 20, 60],
        direction: 'horizontal',
        gutterSize: 3,
    });
    const splitV = Split(['#panentrees', '#pansugg'], {
        sizes: [70, 30],
        direction: 'vertical',
        gutterSize: 3,
    });
})();


/** Load file as bottom script */
/* unplug
var bottom_titre = function() {
    if (!bottom_titre.checks) {
        bottom_titre.checks = document.querySelectorAll(".dico_check");
    }
    for (var i = 0, len = bottom_titre.checks.length; i < len; i++) {
        var input = bottom_titre.checks[i];
        input.addEventListener('click', function(evt) {
            if (this.checked) {
                this.parentNode.classList.add("checked");
            } else {
                this.parentNode.classList.remove("checked");
            }
        });
    }
    var el;
    el = document.getElementById("dico_checkall");
    if (el) {
        span = el.parentNode.getElementsByTagName('span')[0];
        console.log(span);
        el.addEventListener('click', function(evt) {
            var checked = this.checked;
            if (checked) {
                span.old = span.innerText;
                span.innerText = "Tout décocher";
            } else {
                span.innerText = "Tout sélectionner";
            }
            for (var i = 0, len = bottom_titre.checks.length; i < len; i++) {
                if (bottom_titre.checks[i].offsetParent === null) continue; // invisible
                bottom_titre.checks[i].checked = checked;
                if (checked) {
                    bottom_titre.checks[i].parentNode.classList.add("checked");
                } else {
                    bottom_titre.checks[i].parentNode.classList.remove("checked");
                }
            }
        });
    }

}
bottom_titre();
*/
/*
window.addEventListener('beforeunload', (event) => {
    event.returnValue = "Le formulaire n’a pas été enregistré, êtes vous certain de quitter ?";
});
*/