"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
document.addEventListener('DOMContentLoaded', function (event) {
    return __awaiter(this, void 0, void 0, function* () {
        initializeListeners();
        initSelect2Inputs();
    });
});
function initializeListeners() {
    return __awaiter(this, void 0, void 0, function* () {
    });
}
function initSelect2Inputs() {
    return __awaiter(this, void 0, void 0, function* () {
        var select2Inputs = document.querySelectorAll('select.select2');
        select2Inputs.forEach((element) => {
            if (element.getAttribute('data-query-id') !== null) {
                jQuery(element).select2({
                    ajax: {
                        cache: true,
                        dataType: 'json',
                        delay: 250,
                        url: '/SecTrack2/Admin/includes/search.php',
                        type: "POST",
                        data: function (params) {
                            if (params.term === undefined)
                                params.term = "";
                            var query = {
                                Type: 'select-query',
                                searchTerm: params.term,
                                attributeTerm: jQuery(element).attr('data-department-code'),
                                ID: jQuery(element).attr('data-query-id'),
                                tags: jQuery(element).attr('data-tags')
                            };
                            return query;
                        }
                    },
                    placeholder: jQuery(element).attr('placeholder')
                });
            }
            else
                jQuery(element).select2();
            jQuery(element).on('select2:open', () => {
                let select = document.querySelector('.select2-search__field');
                select.focus();
            });
        });
    });
}
