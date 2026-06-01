/**
 * Applies admin drawing prices to simple & complex calculators.
 */
(function (global) {
  'use strict';

  function getPricing() {
    return global.pcdCalculator && global.pcdCalculator.pricing
      ? global.pcdCalculator.pricing
      : null;
  }

  function num(value, fallback) {
    var n = Number(value);
    return Number.isFinite(n) ? n : fallback;
  }

  function mergeSimpleData(data) {
    var pricing = getPricing();
    if (!pricing || !pricing.simple || !data || !data.services) return data;

    var ex = pricing.simple.existing;
    if (ex) {
      if (ex.floor && data.services[2]) {
        data.services[2].unitPriceBySize = Object.assign({}, ex.floor);
      }
      if (ex.elevations && data.services[3]) {
        data.services[3].unitPriceBySize = Object.assign({}, ex.elevations);
      }
      if (ex.sections && data.services[4]) {
        data.services[4].unitPriceBySize = Object.assign({}, ex.sections);
      }
    }
    return data;
  }

  function getSimpleProposedPrice(checkboxId) {
    var pricing = getPricing();
    if (!pricing || !pricing.simple || !pricing.simple.proposed) {
      if (checkboxId === 'others') return 80;
      if (checkboxId === 'doubleStoreyDormers') return 160;
      return 100;
    }
    var proposed = pricing.simple.proposed;
    if (checkboxId && proposed[checkboxId] != null) {
      return num(proposed[checkboxId], num(proposed.default, 100));
    }
    return num(proposed.default, 100);
  }

  var complexExistingInputMap = {
    existingFloorPlans: 'floorPlans',
    existingElevations: 'elevations',
    existingSections: 'sections',
    existingRoofPlans: 'roofPlans',
    existingTopographicalPlans: 'topographicalPlans',
    existingInteriorElevations: 'interiorElevations',
    existingBasicSitePlans: 'basicSitePlans'
  };

  var complexProposedInputMap = {
    proposedFloorPlansAffected: 'floorPlansAffected',
    proposedElevationsAffected: 'elevationsAffected',
    proposedSectionsAffected: 'sectionsAffected',
    proposedRoofPlansAffected: 'roofPlansAffected',
    proposedBasicSitePlansAffected: 'basicSitePlansAffected',
    proposedTopographicalPlansAffected: 'topographicalPlansAffected',
    proposedInteriorElevationsAffected: 'interiorElevationsAffected',
    proposedBasicLocationPlansAffected: 'basicLocationPlansAffected'
  };

  var complexExistingDefaults = {
    floorPlans: 40,
    elevations: 40,
    sections: 40,
    roofPlans: 40,
    topographicalPlans: 40,
    interiorElevations: 20,
    basicSitePlans: 100
  };

  var complexProposedDefaults = {
    floorPlansAffected: 30,
    elevationsAffected: 30,
    sectionsAffected: 30,
    roofPlansAffected: 30,
    basicSitePlansAffected: 30,
    topographicalPlansAffected: 30,
    interiorElevationsAffected: 10,
    basicLocationPlansAffected: 20
  };

  function getComplexExistingPrices() {
    var pricing = getPricing();
    var src = pricing && pricing.complex && pricing.complex.existing
      ? pricing.complex.existing
      : {};
    var out = Object.assign({}, complexExistingDefaults);
    Object.keys(out).forEach(function (key) {
      if (src[key] != null) out[key] = num(src[key], out[key]);
    });
    return out;
  }

  function getComplexProposedPrices() {
    var pricing = getPricing();
    var src = pricing && pricing.complex && pricing.complex.proposed
      ? pricing.complex.proposed
      : {};
    var out = Object.assign({}, complexProposedDefaults);
    Object.keys(out).forEach(function (key) {
      if (src[key] != null) out[key] = num(src[key], out[key]);
    });
    return out;
  }

  function calculateExistingBaseFees(drawings) {
    var p = getComplexExistingPrices();
    return (
      (drawings.floorPlans || 0) * p.floorPlans +
      (drawings.elevations || 0) * p.elevations +
      (drawings.sections || 0) * p.sections +
      (drawings.roofPlans || 0) * p.roofPlans +
      (drawings.topographicalPlans || 0) * p.topographicalPlans +
      (drawings.interiorElevations || 0) * p.interiorElevations +
      (drawings.basicSitePlans || 0) * p.basicSitePlans
    );
  }

  function calculateProposedBaseFees(drawingsAffected) {
    var p = getComplexProposedPrices();
    return (
      (drawingsAffected.floorPlansAffected || 0) * p.floorPlansAffected +
      (drawingsAffected.elevationsAffected || 0) * p.elevationsAffected +
      (drawingsAffected.sectionsAffected || 0) * p.sectionsAffected +
      (drawingsAffected.roofPlansAffected || 0) * p.roofPlansAffected +
      (drawingsAffected.basicSitePlansAffected || 0) * p.basicSitePlansAffected +
      (drawingsAffected.topographicalPlansAffected || 0) * p.topographicalPlansAffected +
      (drawingsAffected.interiorElevationsAffected || 0) * p.interiorElevationsAffected +
      (drawingsAffected.basicLocationPlansAffected || 0) * p.basicLocationPlansAffected
    );
  }

  function setPriceLabelForInput(inputId, amount) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var sub = input.closest('.pcx-subservice');
    if (!sub) return;
    var p = sub.querySelector('.pcx-service-text p');
    if (p) p.textContent = '£' + Math.round(amount) + ' each';
  }

  function refreshComplexPriceLabels() {
    var ex = getComplexExistingPrices();
    Object.keys(complexExistingInputMap).forEach(function (inputId) {
      setPriceLabelForInput(inputId, ex[complexExistingInputMap[inputId]]);
    });
    var pr = getComplexProposedPrices();
    Object.keys(complexProposedInputMap).forEach(function (inputId) {
      setPriceLabelForInput(inputId, pr[complexProposedInputMap[inputId]]);
    });
  }

  function refreshSimpleProposedLabels() {
    var checkboxes = document.querySelectorAll('#pcx-mode-simple input[name="proposedTypes[]"]');
    checkboxes.forEach(function (cb) {
      var label = cb.closest('.checkbox-item');
      if (!label) return;
      var text = label.querySelector('.checkbox-label');
      if (!text) return;
      var base = text.textContent.replace(/\s*\(£[\d,]+\)\s*$/, '').trim();
      var price = getSimpleProposedPrice(cb.id);
      text.textContent = base + ' (£' + Math.round(price) + ')';
    });
  }

  function patchComplexCalculator(root) {
    if (!root || root.__pcdPricingPatched) return;
    root.__pcdPricingPatched = true;
    refreshComplexPriceLabels();
  }

  global.pcdPricingBridge = {
    mergeSimpleData: mergeSimpleData,
    getSimpleProposedPrice: getSimpleProposedPrice,
    calculateExistingBaseFees: calculateExistingBaseFees,
    calculateProposedBaseFees: calculateProposedBaseFees,
    refreshComplexPriceLabels: refreshComplexPriceLabels,
    refreshSimpleProposedLabels: refreshSimpleProposedLabels,
    patchComplexCalculator: patchComplexCalculator
  };
})(window);
