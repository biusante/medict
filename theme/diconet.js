'use strict';

    /**
     *
     */
     function graphInit(div) {
        if (!div) return;
        mysig = Sigmot.sigma(div); // name of graph
    }

        /**
     * 
     * @param {*} input 
     * @returns 
     */
         function graphUp() {
            // populate a graph of words
            if (!mysig) return;
            const pars = Ajix.pars(form);
            // if query, what should I do ?
            if (formData.get('q')) {
                var url = 'data/cooc.json' + "?" + pars;
            } else if (formData.get('senderid') || formData.get('receiverid')) {
                var url = 'data/correswords.json' + "?" + pars;
            } else {
                var url = 'data/wordnet.json' + "?" + pars;
            }
            loadJson(url, function(json) {
                if (!json) {
                    console.log("[Elicom] load error url=" + url)
                    return;
                }
                if (!json.data) {
                    console.log("[Elicom] grap load error\n" + json)
                    return;
                }
                mysig.graph.clear();
                mysig.graph.read(json.data);
                mysig.startForce();
                mysig.refresh();
            });
        }