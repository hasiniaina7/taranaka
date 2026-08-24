import { createChart } from 'family-chart';
import 'family-chart/styles/family-chart.css';

function toChartId(id) {
    return `p${id}`;
}

/**
 * Flatten the fully-computed descendant tree (spec 004) into family-chart's
 * flat {id, data, rels} array. The whole tree is already present, so no
 * further Livewire round-trips are needed: family-chart's own expand/collapse
 * UI is enough.
 */
function buildDescendantChartData(tree) {
    const nodes = new Map();

    const visit = (node, parentChartId) => {
        const chartId = toChartId(node.id);

        if (!nodes.has(chartId)) {
            nodes.set(chartId, {
                id: chartId,
                data: {
                    label: node.name,
                    avatar: node.photo_url,
                    isLiving: node.is_living,
                    personId: node.id,
                    pending: false,
                },
                rels: { parents: [], spouses: [], children: [] },
            });
        }

        const datum = nodes.get(chartId);

        if (parentChartId) {
            if (!datum.rels.parents.includes(parentChartId)) {
                datum.rels.parents.push(parentChartId);
            }

            const parent = nodes.get(parentChartId);

            if (parent && !parent.rels.children.includes(chartId)) {
                parent.rels.children.push(chartId);
            }
        }

        attachPartners(nodes, datum, node.partners);

        (node.children ?? []).forEach((child) => visit(child, chartId));
    };

    visit(tree, null);

    return Array.from(nodes.values());
}

/**
 * Flatten the currently loaded/expanded ancestor tree (spec 005). Nodes the
 * visitor has not expanded yet are represented as "pending" stubs so a click
 * can trigger the real Livewire branch load (FR-011 still applies: only a
 * genuine click, not a drag, triggers it).
 */
function buildAncestorChartData(tree) {
    const nodes = new Map();

    const visit = (node, childChartId) => {
        if (node.unknown) {
            return;
        }

        const chartId = toChartId(node.id);
        let datum = nodes.get(chartId);

        if (!datum) {
            datum = {
                id: chartId,
                data: {
                    label: node.name,
                    avatar: node.photo_url ?? null,
                    isLiving: node.living,
                    personId: node.id,
                    pending: node.canExpand && !node.expanded,
                },
                rels: { parents: [], spouses: [], children: [] },
            };
            nodes.set(chartId, datum);
        }

        if (childChartId && !datum.rels.children.includes(childChartId)) {
            datum.rels.children.push(childChartId);
        }

        attachPartners(nodes, datum, node.partners);

        (node.children ?? []).forEach((parentNode) => {
            if (parentNode.unknown) {
                const unknownChartId = `unknown-${chartId}-${parentNode.relationship}`;

                if (!nodes.has(unknownChartId)) {
                    nodes.set(unknownChartId, {
                        id: unknownChartId,
                        data: {
                            label: parentNode.label,
                            avatar: null,
                            isLiving: null,
                            personId: null,
                            pending: false,
                        },
                        rels: { parents: [], spouses: [], children: [chartId] },
                    });
                }

                if (!datum.rels.parents.includes(unknownChartId)) {
                    datum.rels.parents.push(unknownChartId);
                }

                return;
            }

            const parentChartId = toChartId(parentNode.id);

            if (!datum.rels.parents.includes(parentChartId)) {
                datum.rels.parents.push(parentChartId);
            }

            visit(parentNode, chartId);
        });
    };

    visit(tree, null);

    return Array.from(nodes.values());
}

/**
 * A partner is frequently not itself a blood relative already present in the
 * payload (a descendant's spouse, an ancestor's second marriage), so a
 * minimal stub card is synthesized from the name/photo carried on
 * `partners` (research.md, data-model.md).
 */
function attachPartners(nodes, datum, partners) {
    (partners ?? []).forEach((partner) => {
        const partnerChartId = toChartId(partner.id);

        if (!datum.rels.spouses.includes(partnerChartId)) {
            datum.rels.spouses.push(partnerChartId);
        }

        if (!nodes.has(partnerChartId)) {
            nodes.set(partnerChartId, {
                id: partnerChartId,
                data: {
                    label: partner.name,
                    avatar: partner.photo_url,
                    isLiving: null,
                    personId: partner.id,
                    pending: false,
                },
                rels: { parents: [], spouses: [], children: [] },
            });
        }

        const partnerDatum = nodes.get(partnerChartId);

        if (!partnerDatum.rels.spouses.includes(datum.id)) {
            partnerDatum.rels.spouses.push(datum.id);
        }
    });
}

window.familyTreeCanvas = ({ mode, tree, profileUrlTemplate }) => ({
    mode,
    profileUrlTemplate,
    chart: null,

    init() {
        this.render(tree);
    },

    update(freshTree) {
        this.render(freshTree);
    },

    render(tree) {
        const data =
            this.mode === 'ancestor'
                ? buildAncestorChartData(tree)
                : buildDescendantChartData(tree);

        if (!this.chart) {
            this.chart = createChart(this.$refs.canvas, data);
            this.chart
                .setCardSvg()
                .setCardDisplay((datum) => datum.data.label)
                .setOnCardClick((event, datum) => this.handleCardClick(datum));
            // Partner cards are already synthesized in the payload (see attachPartners),
            // so family-chart's own "missing second parent" placeholder would be a
            // redundant, unlabeled duplicate card next to every single-blood-parent child.
            this.chart.setSingleParentEmptyCard(false);
            this.chart.updateTree({ initial: true });

            return;
        }

        this.chart.updateData(data);
        this.chart.updateTree({ initial: false });
    },

    handleCardClick(datum) {
        if (!datum.data.data.personId) {
            return;
        }

        if (datum.data.data.pending) {
            this.$wire.call('toggleBranch', datum.data.data.personId);

            return;
        }

        window.location.href = this.profileUrlTemplate.replace(
            '__ID__',
            datum.data.data.personId,
        );
    },
});
