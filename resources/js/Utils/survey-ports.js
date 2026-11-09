// Keep the customer's existing assignment selectable, but never bypass fault/stop.
// The server still verifies ownership and conflicts inside a transaction.
export function surveyPortNumbers(odp, survey = null, customer = null) {
    if (!odp) return [];
    const ownSurveyPort = survey?.feasibility === 'feasible'
        && Number(survey.odp_id) === Number(odp.id) ? Number(survey.port_number) : null;
    const ownOntPort = customer?.ont && Number(customer.ont.odp_id) === Number(odp.id)
        ? Number(customer.ont.port_number) : null;
    const ownPort = ownSurveyPort || ownOntPort;

    const blocked = new Set((odp.ports || [])
        .filter(port => ['fault', 'stop'].includes(port.status)
            || (['used', 'reserved'].includes(port.status) && Number(port.port_number) !== ownPort))
        .map(port => Number(port.port_number)));
    return Array.from({ length: Number(odp.total_ports) || 0 }, (_, index) => index + 1)
        .filter(port => !blocked.has(port));
}
