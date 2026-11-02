import test from 'node:test';
import assert from 'node:assert/strict';
import { surveyPortNumbers } from '../../resources/js/Utils/survey-ports.js';

const odp = { id: 2, total_ports: 4, ports: [
    { port_number: '1', status: 'used' },
    { port_number: 2, status: 'reserved' },
    { port_number: 3, status: 'fault' },
] };
test('new survey cannot select occupied or faulty ports', () => {
    assert.deepEqual(surveyPortNumbers(odp), [4]);
});
test('editing preserves own used or reserved port with numeric/string IDs', () => {
    assert.deepEqual(surveyPortNumbers(odp, { odp_id: '2', port_number: 1, feasibility: 'feasible' }), [1, 4]);
    assert.deepEqual(surveyPortNumbers(odp, { odp_id: 2, port_number: '2', feasibility: 'feasible' }), [2, 4]);
});
test('own port on another ODP does not make an occupied target selectable', () => {
    assert.deepEqual(surveyPortNumbers(odp, { odp_id: 1, port_number: 1, feasibility: 'feasible' }), [4]);
});
test('own port cannot bypass fault or stop flags', () => {
    for (const status of ['fault', 'stop']) {
        assert.deepEqual(surveyPortNumbers({ id: 2, total_ports: 1, ports: [{ port_number: 1, status }] },
            { odp_id: 2, port_number: 1, feasibility: 'feasible' }), []);
    }
});
test('missing selection is safe', () => assert.deepEqual(surveyPortNumbers(null), []));
