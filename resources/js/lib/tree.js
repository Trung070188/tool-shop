export function treeFindChecked(root, callback) {
    if (Array.isArray(root)) {
        root.forEach(node => {
            treeFindChecked(node, callback);
        })
    } else {
        if (root.checked) {
            callback(root);
        } else {
            if (Array.isArray(root.data_children)) {
                root.data_children.forEach(node => {
                    treeFindChecked(node, callback);
                });
            }
        }
    }
}

export function treeValidate(root) {
    let idMap = {};
    treeIterate(root, (node) => {
        if (idMap[node.id]) {
            console.error(`TreeValidateError: ID bị trùng ${node.id}`);
        }

        idMap[node.id] = true;
    });
}

export function treeIterate(root, callback, level = 0) {
    if (!root) {
        throw new Error('iterateTree: root is null')
    }

    if (typeof callback !== 'function') {
        throw new Error('iterateTree: callback must be a function');
    }

    if (Array.isArray(root)) {
        root.forEach(node => {
            treeIterate(node, callback);
        })
    } else {
        let result = callback(root, level);
        if (result === false) {
            console.log('treeIterate break');
            return;
        }

        if (Array.isArray(root.data_children)) {
            root.data_children.forEach(node => {
                treeIterate(node, callback, level + 1);
            });
        }
    }

}

export function treeFindPath(root, child) {
    const typeOfChild = typeof child;
    if (typeOfChild !== "object") {
        child = treeFindNode(root, child);

        if (!child) {
            return [];
        }
    }

    const result = [child];
    const r2 = treeFindParents(root, child);

    result.push.apply(result, r2);
    return result;
}

export function treeFindParents(root, child) {
    const parentId = child.parent_id;

    if (parentId) {

        const result = [];
        treeIterate(root, (node) => {
            if (node.id == parentId) {
                result.push(node);
                const parentResult = treeFindParents(root, node);
                result.push.apply(result, parentResult);
            }
        });

        return result;
    } else {
        return [];
    }
}

export function treeFindNode(root, id) {
    let result = null;

    treeIterate(root, (node) => {
        if (node.id == id) {
            result = node;
            //return false;
        }
    });

    return result;
}
