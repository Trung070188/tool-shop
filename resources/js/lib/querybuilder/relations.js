import {createQuery} from "./QueryBuilder";
import {clone} from "../../utils";

/**
 * HasManyRelations
 * @param {Array<any>} entries
 * @param {object} relation
 * @param {string} relation.table
 * @param {string} relation.alias
 * @param {string} relation.foreignKey
 * @param {string} relation.localKey
 * @param {Array<string>} relation.select
 * @param {(QueryBuilder) => void} relation.query
 * @param {callback} relation.format
 * @returns {Promise<void>}
 */
export async function withRelationHasMany(entries, relation) {
    const entryLen = entries.length;
    const values = [];
    const valueMap = {};
    relation = clone(relation);

    if (!relation.localKey) {
        relation.localKey = 'id'
    }

    for (let i = 0; i < entryLen; i++) {
        const entry = entries[i];
        if (!entry) {
            throw new Error(`WithHasMany: Entry ${i} is NULl`)
        }

        const v = entry[relation.localKey];
        // Unique checks
        if (v && !valueMap.hasOwnProperty(v)) {
            values.push(v);
            valueMap[v] = true
        }
    }

    let childEntries = [], childEntryMap = {};
    if (values.length > 0) {
        const childQuery = createQuery(relation.table);
        if (typeof relation.query === 'function') {
            relation.query(childQuery)
        }

        if (relation.select && relation.select.length > 0) {
            childQuery.select(relation.select)
        }

        childEntries = await childQuery.whereIn(
            relation.foreignKey, values
        ).get();

        childEntries.forEach(p => {
            if (!childEntryMap[p[relation.foreignKey]]) {
                childEntryMap[p[relation.foreignKey]] = [];
            }
            childEntryMap[p[relation.foreignKey]].push(p);
        });
    }

    entries.forEach(entry => {
        const k = entry[relation.localKey];
        if (childEntryMap.hasOwnProperty(k)) {
            let entries = childEntryMap[k];
            if (relation.format) {
                entries = relation.format(entries)
            }
            entry[relation.alias] = entries
        } else {
            entry[relation.alias] = [];
        }
    })
}

/**
 * BeLongs to relation
 * @param {Array<any>} entries
 * @param {object} relation
 * @param {string} relation.table
 * @param {string} relation.alias
 * @param {string} relation.foreignKey
 * @param {string} relation.ownerKey
 * @param {Array<string>} relation.select
 * @param {(QueryBuilder) => void} relation.query
 * @param {callback} relation.format
 * @returns {Promise<void>}
 */
export async function withRelationBelongsTo(entries, relation) {
    const entryLen = entries.length;
    const values = [];
    const valueMap = {};
    relation = clone(relation);

    if (!relation.alias) {
        relation.alias = relation.table
    }

    if (!relation.ownerKey) {
        relation.ownerKey = 'id'
    }

    if (!relation.select) {
        relation.select = []
    }

    for (let i = 0; i < entryLen; i++) {
        const entry = entries[i];

        if (!entry) {
            throw new Error(`_withBelongsTo: Entry ${i} is NULl`)
        }


        const v = entry[relation.foreignKey];
        // Unique checks
        if (v && !valueMap.hasOwnProperty(v)) {
            values.push(v);
            valueMap[v] = true
        }
    }

    let parenEntries = [], parentEntryMap = {};
    if (values.length > 0) {
        const parentQuery = createQuery(relation.table);
        if (typeof relation.query === 'function') {
            relation.query(parentQuery)
        }
        if (relation.select.length > 0) {
            parentQuery.select(relation.select)
        }

        parenEntries = await parentQuery.whereIn(
            relation.ownerKey, values
        ).get();

        parenEntries.forEach(p => {
            parentEntryMap[p[relation.ownerKey]] = p;
        })
    }

    entries.forEach(entry => {
        const k = entry[relation.foreignKey];
        if (parentEntryMap.hasOwnProperty(k)) {
            let e = parentEntryMap[k];
            if (relation.format) {
                e = relation.format(e)
            }
            entry[relation.alias] = e
        } else {
            entry[relation.alias] = null;
        }
    })
}

