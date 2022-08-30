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
    function loadLines(div, url, callback, sep = '\n') {
        return new Promise(function(resolve, reject) {
            if (div.xhr) { // still loading, abort
                div.xhr.abort();
                delete div.xhr;
            }
            var xhr = new XMLHttpRequest();
            div.xhr = xhr;
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
        loadLines(suggest, url, function(json) {
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
            console.log('[Formajax] A <form> is required to init Formajax');
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
        // if still loading, replace, 
        // if (div.loading) return; // still loading
        div.loading = true;
        if (!append) {
            div.innerText = '';
        }
        let url = div.dataset.url + "?" + pars();
        loadLines(div, url, function(html) {
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

    function selfOrAncestor(el, name) {
        while (el.tagName.toLowerCase() != name) {
            el = el.parentNode;
            if (!el) return false;
            let tag = el.tagName.toLowerCase();
            if (tag == 'div' || tag == 'nav' || tag == 'body') return false;
        }
        return el;
    }


    return {
        divLoad: divLoad,
        init: init,
        inputDel: inputDel,
        insLine: insLine,
        LF: LF,
        loadLines: loadLines,
        pars: pars,
        selfOrAncestor: selfOrAncestor,
        suggestInit: suggestInit,
    }
}();

/**
 * Pilot of Medit app
 */
class Medict {
    /* the form */
    static form;
    /* the viewer */
    static viewer;
    /** default image for viewer */
    static imgLo;
    /** Hi resolution */
    static imgHi;

    constructor() {
        if (this instanceof Medict) {
            throw Error('This is a static class, cannot be instantiated.');
        }
    };

    static init() {
        // init the form
        Medict.form = document.forms['medict'];
        if (!Medict.form) return;

        Medict.titresInit();

        Formajax.init(Medict.form);
        // prevent submit before afect it as event
        Medict.form.addEventListener('submit', (e) => {
            e.preventDefault();
            Formajax.divLoad('mots');
            Medict.historyChange();
            return false;
        }, true);
        // send submit when suggest change
        Medict.form.q.addEventListener('input', (e) => {
            Medict.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
        }, true);

        window.onpopstate = function(e) {
            var state = e.state;
            // a state produced by the app
            if (state !== null) {
                Medict.winload();
            }
        };

        // for efficiency, put a click event on the terme container, so they can change without changin the event
        const mots = document.getElementById('mots');
        if (mots) mots.addEventListener('click', Medict.motsClick);
        const entrees = document.getElementById('entrees');
        if (entrees) entrees.addEventListener('click', Medict.entreesClick);
        const sugg = document.getElementById('sugg');
        if (sugg) {
            sugg.addEventListener('click', Medict.entreesClick);
            sugg.addEventListener('click', Medict.suggClick);
        }
        const trad = document.getElementById('trad');
        if (trad) {
            trad.addEventListener('click', Medict.entreesClick);
            trad.addEventListener('click', Medict.suggClick);
        }
        Medict.setViewer('viewcont');
        // Update interface onload or with back, after setting viewer
        Medict.winload();

        // events on the prev / next buttons for facs image
        const aref = document.getElementById('medica-ext');
        let but = document.getElementById('medica-prev');
        if (aref && but) {
            but.onclick = function(e) {
                e.preventDefault();
                if (!aref.dataset.p || !aref.dataset.cote) return;
                Medict.facs(aref.dataset.cote, parseInt(aref.dataset.p, 10) - 1);
                Medict.historyChange();
                return false;
            }
        }
        but = document.getElementById('medica-next');
        if (aref && but) {
            but.onclick = function(e) {
                e.preventDefault();
                if (!aref.dataset.cote || !aref.dataset.p) return;
                console.log(aref.dataset.p + 1);
                Medict.facs(aref.dataset.cote, parseInt(aref.dataset.p, 10) + 1);
                Medict.historyChange();
                return false;
            }
        }

    }

    static sanitize(html) {
        if (!Medict.decoder) Medict.decoder = document.createElement('div');
        Medict.decoder.innerHTML = html;
        return Medict.decoder.textContent;
    }

    static titresInit() {
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
                Medict.historyChange();
                // submit form
                this.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
                Formajax.divLoad('entrees');
                Formajax.divLoad('sugg');
                Medict.titreLabel();
            }
            // loop on all checkbox
        const ticklist = modal.querySelectorAll("input[type=checkbox][name=f]");
        for (let i = 0; i < ticklist.length; ++i) {
            const checkbox = ticklist[i];
            if (checkbox.checked) {
                checkbox.parentNode.classList.add("checked");
            }
            checkbox.addEventListener('change', titreChange);
        }
        const allF = document.getElementById("allF");
        if (allF) {
            allF.addEventListener("change", function(e) {
                const flag = this.checked;
                // all or none, exclude url par
                // update URL but do not add entry in history
                Medict.historyChange(null, ['f']);
                /*
                if (flag) {
                    document.getElementById('allFCheck').style.display = 'none';
                    document.getElementById('allFUncheck').style.display = 'block';
                } else {
                    document.getElementById('allFCheck').style.display = 'block';
                    document.getElementById('allFUncheck').style.display = 'none';
                }
                */
                for (let i = 0; i < ticklist.length; ++i) {
                    const checkbox = ticklist[i];
                    checkbox.checked = flag;
                    if (flag) checkbox.parentNode.classList.add("checked");
                    else checkbox.parentNode.classList.remove("checked");
                }
                // sélecteurs de tags
                const alltag = modal.querySelectorAll(".selector.tag input");
                for (let i = 0; i < alltag.length; ++i) {
                    const checkbox = alltag[i];
                    checkbox.checked = flag;
                }
                // submit form
                this.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true }));
                Formajax.divLoad('entrees');
                Formajax.divLoad('sugg');
                Medict.titreLabel();
            });
        }
        const alltag = modal.querySelectorAll(".selector.tag input");
        for (let i = 0; i < alltag.length; ++i) {
            const checkbox = alltag[i];
            const tag = checkbox.value;
            if (!tag) continue;
            checkbox.addEventListener("change", function(e) {
                const flag = this.checked;
                let selector = "input." + tag;
                if (!flag) {
                    selector = 'input[class="' + tag + '"]';
                }
                const tiktag = modal.querySelectorAll(selector);
                for (let x = 0; x < tiktag.length; x++) {
                    const tik = tiktag[x];
                    tik.checked = flag;
                    if (flag) tik.parentNode.classList.add("checked");
                    else tik.parentNode.classList.remove("checked");
                }
                this.form.dispatchEvent(new Event('submit', { "bubbles": true, "cancelable": true })); // met à jour l’url
                Formajax.divLoad('entrees');
                Formajax.divLoad('sugg');
                Medict.titreLabel();
            });
        }
        Medict.titresOrdre();
    }

    /**
     * Trier les titres par attribut
     */
    static titresOrdre() {
        const nodeset = document.querySelectorAll("div.titre");
        const divs = Array.from(nodeset);
        const sortitres = document.getElementById('sortitres');
        const titres_cols = document.getElementById('titres_cols');
        sortitres.addEventListener("change", function(e) {
            const field = this.value;
            divs.sort(function(a, b) {
                return a.dataset[field].localeCompare(b.dataset[field]);
            });
            divs.forEach(function(el) {
                titres_cols.appendChild(el);
            });
        });
    }

    /**
     * Construire un nom aide-mémoire pour une sélection
     */
    static titreLabel() {
        let count = 0;
        let datemin = 20000;
        let datemax = 0;
        // loop on all checkbox
        const tiklist = document.querySelectorAll("input[type=checkbox][name=f]");
        for (let i = 0; i < tiklist.length; i++) {
            const tik = tiklist[i];
            if (!tik.checked) continue;
            const annee = tik.parentElement.dataset.annee;
            count++;
            datemin = Math.min(datemin, annee);
            datemax = Math.max(datemax, annee);
            const an_max = tik.parentElement.dataset.an_max;
            if (an_max) datemax = Math.max(datemax, an_max);
        }
        let label = "Tous les titres";
        if (count) {
            label = datemin;
            if (datemin != datemax) label += ' – ' + datemax;
            if (1 == count) label += ' (1 titre)';
            else label += ' (' + count + ' titres)';
        }
        var div = document.getElementById('titres_open');
        if (div) div.innerHTML = label;
    }

    /**
     * update interface onload or with back history
     */
    static winload() {
        // TODO, update title filter, mutiple

        // update form with url values, will not work well with multiple values
        (new URL(window.location.href)).searchParams.forEach(function(value, key) {
            if (!Medict.form[key]) return;
            Medict.form[key].value = value;
        });
        Formajax.divLoad('mots');
        Formajax.divLoad('entrees');
        Formajax.divLoad('sugg');
        Formajax.divLoad('trad');
        // équiper les suggesteurs, mais yapa
        /*
        const inputs = document.querySelectorAll("input.multiple[data-url]");
        for (let i = 0; i < inputs.length; i++) {
            Formajax.suggestInit(inputs[i]);
        }
        */
        // 
        const url = new URL(window.location);
        Medict.facs(
            url.searchParams.get('cote'),
            url.searchParams.get('p'),
            Medict.sanitize(url.searchParams.get('bibl')),
        );
        /* ?
        Medict.viewer.resize();
        Medict.viewer.update();
        */
    }

    /**
     * Push an entry in history
     */
    static historyPush(include, exclude) {
        const url = new URL(window.location);
        url.search = Formajax.pars(include, exclude);
        window.history.pushState({}, '', url);
    }

    /**
     * update URL but do not add entry in history
     */
    static historyChange(include, exclude) {
        const url = new URL(window.location);
        url.search = Formajax.pars(include, exclude);
        window.history.replaceState({}, '', url);
    }

    static imgError(e) {
        const img = e.currentTarget;
        img.onerror = null;
        console.log(this.src + " ERROR");
        let url = img.src;
        fetch(
            url, { cache: 'reload', mode: 'no-cors' }
        ).then((response) => {
            console.log(response.status);
            if (response.status !== 200) {
                console.log(response.status + " fetch reload error: " + url);
                img.onerror = Medict.imgErrorLast;
                img.srcOld = img.src;
                // on error with fetch, retry 3rd but last attempt
                // this works nicely but no good for cache
                url += "?";
                img.src = url.substring(0, url.indexOf("?")) + "?time=" + new Date();
                return;
            }
            console.log("Fetch reload OK: " + url);
            img.src = url;
        });
    }

    static imgErrorLast(e) {
        const img = e.currentTarget;
        img.onerror = null;
        img.src = img.srcOld;
    }

    static viewerOptions = {
        transition: false,
        inline: true,
        navbar: 0,
        // inheritedAttributes: null,
        // minWidth: '100%', 
        toolbar: {
            width: function(e) {
                let cwidth = Medict.viewer.viewer.offsetWidth;
                let iwidth = Medict.viewer.imageData.naturalWidth;
                let zoom = cwidth / iwidth;
                Medict.viewer.zoomTo(zoom);
                Medict.viewer.moveTo(0, Medict.viewer.imageData.y);
            },
            zoomIn: true,
            zoomOut: true,
            oneToOne: true,
            /*
            flipVertical: function() {
                const img = Medict.viewerImg;
                if (img.srcHi) img.src = img.srcHi;
                Medict.viewer.update();
                // Medict.viewer.resize();
            },
            */
            ld: function() {
                Medict.viewer.view(0);
                return this;
            },
            hd: function() {
                Medict.viewer.view(1);
                return this;
            },
        },
        title: function(image) {
            return null;
        },
        filter(image) {
            // do not show empty images
            return image.src;
        },
        show: true,
        full() {
            console.log(this.viewer);
        },
        viewed() {

            // default zoom on load, image width
            let cwidth = Medict.viewer.viewer.offsetWidth;
            let iwidth = Medict.viewer.imageData.naturalWidth;
            let zoom = cwidth / iwidth;
            Medict.viewer.zoomTo(zoom);
            Medict.viewer.moveTo(0, 0);
        },
        zoomed() {
            // record last Zoom level
            Medict.viewer.lastZoomRequested = Medict.viewer.imageData.ratio;
        },
    }

    static setViewer(id) {
        const div = document.getElementById(id);
        if (!div) return;
        let els = div.getElementsByTagName('img');
        if (!els || els.length < 1) return;
        Medict.imgLo = els[0];
        Medict.imgHi = els[1];
        Medict.viewer = new Viewer(div, Medict.viewerOptions);
    }

    /**
     * Behavior of suggested term
     * @param {*} e 
     * @returns 
     */
    static suggClick(e) {
        let a = Formajax.selfOrAncestor(e.target, 'a');
        if (!a) return;
        if (!a.classList.contains('sugg')) return;
        e.preventDefault();
        const pars = new URLSearchParams(a.search);
        const q = pars.get('q');
        if (!q) return;
        Medict.form.q.value = q;
        // form.t.value = q; // non juste l’index
        Formajax.divLoad('mots');
    }

    /**
     * Behavior of entries
     * @param {*} e 
     * @returns 
     */
    static entreesClick(e) {
        let a = Formajax.selfOrAncestor(e.target, 'a');
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
        const p = found[1];
        Medict.facs(cote, p, a.innerHTML);
        Medict.historyPush();



        // change class
        if (document.lastEntree) document.lastEntree.classList.remove('active');
        if (a.classList.contains("active")) {
            a.classList.remove('active');
        } else {
            document.lastEntree = a;
            a.classList.add('active');
        }
    }



    static facs(cote, p, bibl) {
        if (!cote || !p) return;
        p = Medict.pad(p, 4);
        // Biusanté, lien page
        // const href = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&cote=' + cote + '&p=' + p;
        const href = 'https://www.biusante.parisdescartes.fr/histmed/medica/page?' + cote + '&p=' + p;

        // Biusanté, img moyenne
        const srcLo = 'https://www.biusante.parisdescartes.fr/images/livres/' + cote + '/' + p + '.jpg';
        /*
        // Archives.org, iiif, lent
        const srcHi = 'https://iiif.archivelab.org/iiif/BIUSante_' + cote + '$' + (p - 1) + '/full/full/0/default.jpg';
        */
        // Biusante, iiif, cassé
        const srcHi = 'https://www.biusante.parisdescartes.fr/iiif/2/bibnum:' + cote + ":" + p + '/full/full/0/default.jpg';
        // Castelli pas de basse def
        if (['07399'].includes(cote)) {
            Medict.imgLo.src = 'https://www.biusante.parisdescartes.fr/iiif/2/bibnum:' + cote + ":" + p + '/full/pct:50/0/default.jpg';;
            Medict.imgHi.src = srcHi;
        } else {
            Medict.imgLo.src = srcLo;
            Medict.imgHi.src = srcHi;
        }
        // Medict.viewer.ready = true; // force abort of current loading
        Medict.viewer.update(); // let viewer show a waiting roll

        const link = document.getElementById('medica-ext');
        link.href = href;
        // store cote and page here to help the prev / next button
        link.dataset.cote = cote;
        link.dataset.p = p;
        if (bibl) link.innerHTML = bibl;
        else bibl = link.innerHTML;
        Medict.form['bibl'].value = Medict.sanitize(bibl);
        Medict.form['cote'].value = cote;
        Medict.form['p'].value = p;
    }

    static pad(num, width) {
        var s = "000000000" + num;
        return s.substring(s.length - width);
    }



    /**
     * When click in mots, do things
     */
    static motsClick(e) {
        e.preventDefault();
        // catch a link inside column of terms
        let a = Formajax.selfOrAncestor(e.target, 'a');
        if (!a) return;
        const pars = new URLSearchParams(a.search);
        const terme = pars.get('t');

        // push history
        Medict.form['t'].value = terme;
        Medict.historyPush();


        // Update frames
        Formajax.divLoad('entrees');
        Formajax.divLoad('sugg');
        Formajax.divLoad('trad');
        // change class
        if (document.lastIndex) document.lastIndex.classList.remove('active');
        if (a.classList.contains("active")) {
            a.classList.remove('active');
        } else {
            document.lastIndex = a;
            a.classList.add('active');
        }
    }
}


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

    Medict.init();
    const splitH = Split(['#col1', '#col2', '#col3'], {
        sizes: [18, 22, 60],
        direction: 'horizontal',
        gutterSize: 3,
    });
    const splitV = Split(['#panentrees', '#sugg_trad'], {
        sizes: [70, 30],
        direction: 'vertical',
        gutterSize: 3,
    });
    // hook to update image viewer
    Split.dragEnd = function(e) {
        Medict.viewer.resize();
        Medict.viewer.update();
    };
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